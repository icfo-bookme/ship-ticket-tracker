<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\CoPassenger;
use App\Models\Payment;
use App\Models\SalesSource;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\VerifyTracker;
use App\Models\WhatsappDetail;
use App\Services\AuditLogService;
use App\Services\Finance\PaymentProofStorage;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ShipTicketSaleService
{
    public function __construct(
        private readonly PaymentProofStorage $paymentProofs,
        private readonly SaleFinancialService $saleFinancials,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function delete(int $saleId): void
    {
        $this->auditLogs->capture(ShipTicketSale::findOrFail($saleId), fn (ShipTicketSale $sale): ?bool => $sale->delete(), 'deleted');
    }

    public function restore(int $saleId): ShipTicketSale
    {
        $sale = ShipTicketSale::onlyTrashed()->findOrFail($saleId);
        $this->auditLogs->capture($sale, fn (ShipTicketSale $locked): bool => $locked->restore(), 'restored');

        return $sale->refresh();
    }

    public function create(array $data, array $input): ShipTicketSale
    {
        $data = $this->normalizeFinancials($data, $input);
        $salesSource = SalesSource::query()
            ->whereKey($data['sales_source_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $data['sales_source'] = $salesSource->name;
        $saleAttributes = $data;
        unset($saleAttributes['received_amount']);
        unset($saleAttributes['ticket_value']);
        unset($saleAttributes['due_amount']);

        $ticketSale = DB::transaction(function () use ($saleAttributes, $input): ShipTicketSale {
            $ticketSale = ShipTicketSale::create($saleAttributes);

            $this->createCoPassengers($ticketSale, $input['co_passengers'] ?? [], true);
            $this->createPayments(
                $ticketSale,
                $input['payment_methods'] ?? [],
                true,
            );
            $this->createTicketCategories($ticketSale, $input['ticket_categories'] ?? []);

            $this->auditLogs->record($ticketSale, 'created', newValues: $this->auditLogs->snapshot($ticketSale));

            return $ticketSale;
        });

        $this->appendAdminSaleToSheet($ticketSale, $data, $input);

        return $ticketSale;
    }

    public function createPublic(array $data, array $input): ShipTicketSale
    {
        $data = $this->normalizeFinancials($data, $input, true);
        $whatsapp = null;

        if (! empty($input['sales_source'])) {
            $whatsapp = WhatsappDetail::where('form_no', $input['sales_source'])->first();
            $data['sales_source'] = $whatsapp?->whatsapp_number;
        }

        $saleAttributes = $data;
        unset($saleAttributes['received_amount']);
        unset($saleAttributes['ticket_value']);
        unset($saleAttributes['due_amount']);

        $ticketSale = DB::transaction(function () use ($saleAttributes, $input): ShipTicketSale {
            $ticketSale = ShipTicketSale::create($saleAttributes);

            $this->createCoPassengers($ticketSale, $input['co_passengers'] ?? [], false);
            $this->createPayments($ticketSale, $input['payment_methods'] ?? [], false);
            $this->createTicketCategories($ticketSale, $input['ticket_categories'] ?? []);

            $this->auditLogs->record($ticketSale, 'created', newValues: $this->auditLogs->snapshot($ticketSale), system: true);

            return $ticketSale;
        });

        $this->appendPublicSaleToSheet($data, $input, $whatsapp);

        return $ticketSale;
    }

    public function update(ShipTicketSale $sale, array $data, array $input): ShipTicketSale
    {
        $connection = $sale->getConnection();
        $connection->beginTransaction();

        try {
            $sale = $sale->newQuery()->lockForUpdate()->findOrFail($sale->getKey());
            $before = $this->auditLogs->snapshot($sale);

            if (array_key_exists('departure_quantity', $input) || array_key_exists('return_quantity', $input)) {
                $ticketCount = array_sum(array_map('intval', $input['departure_quantity'] ?? []))
                    + array_sum(array_map('intval', $input['return_quantity'] ?? []));

                if ($ticketCount < 1) {
                    throw ValidationException::withMessages([
                        'departure_quantity' => 'At least one ticket category must have a quantity of 1 or more.',
                    ]);
                }

                $data['ticket_value'] = $this->saleFinancials->ticketValueFromPackageQuantities(
                    $data['ship_id'] ?? $sale->ship_id,
                    $data['return_date'] ?? $sale->return_date,
                    $input['departure_quantity'] ?? [],
                    $input['return_quantity'] ?? [],
                );
            } else {
                $data['ticket_value'] = $sale->ticket_value;
            }
            $data = $this->normalizeFinancials($data, $input);

            $sale->update([
                'customer_name' => $data['customer_name'],
                'customer_mobile' => $data['customer_mobile'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'whatsapp_username' => $data['whatsapp_username'] ?? null,
                'email' => $data['email'],
                'nid' => $data['nid'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'] ?? null,
                'collect_from_office' => $data['collect_from_office'],
                'ship_id' => $data['ship_id'],
                'company_id' => $data['company_id'],
                'journey_date' => $data['journey_date'],
                'return_date' => $data['return_date'],
                'other_fee' => $data['other_fee'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_payable' => $data['total_payable'] ?? 0,
                'sales_source' => $data['sales_source'],
                // sold_by is locked after creation: the edit form shows it disabled.
                'sold_by' => $sale->sold_by,
                'issued_date' => $data['issued_date'],
                'status' => $sale->status,
                'remark1' => $data['remark1'],
                'remark2' => $data['remark2'],
            ]);

            if (! empty($input['departure_quantity']) || ! empty($input['return_quantity'])) {
                $this->updatePackageCategories($sale, $data);
            }

            $this->updatePayments($sale, $data['payments'] ?? []);
            $sale->refresh();
            $this->updateCoPassengers($sale, $data['co_passengers'] ?? []);
            $this->addPrintedTickets($sale, $input['additional_pdf'] ?? []);

            if (array_key_exists('shipment_id', $data)) {
                $shipmentId = trim((string) $data['shipment_id']);

                if ($shipmentId === '') {
                    $sale->shipment()->delete();
                } else {
                    $sale->shipment()->updateOrCreate([], ['shipment_id' => $shipmentId]);
                }
            }

            $this->auditLogs->record($sale, 'updated', $before, $this->auditLogs->snapshot($sale));

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        }

        return $sale;
    }

    public function updateIssue(ShipTicketSale $sale, array $data): ShipTicketSale
    {
        return $this->auditLogs->capture($sale, fn (ShipTicketSale $locked): ShipTicketSale => $this->performUpdateIssue($locked, $data));
    }

    private function performUpdateIssue(ShipTicketSale $sale, array $data): ShipTicketSale
    {
        if (array_key_exists('whatsapp', $data) || array_key_exists('whatsapp_username', $data)) {
            $sale->update([
                'whatsapp' => $data['whatsapp'] ?? null,
                'whatsapp_username' => $data['whatsapp_username'] ?? null,
            ]);
        }

        $hasGroupingChoice = array_key_exists('group_tickets', $data);
        $groupById = $hasGroupingChoice
            ? (($data['group_tickets'] ?? null) === 'yes' ? (int) $data['group_by_id'] : $sale->id)
            : ($sale->printedTickets()->latest('id')->value('group_by_id') ?? $sale->id);

        $data['pdf'] = array_merge(
            array_values($data['pdf'] ?? []),
            array_values($data['additional_pdf'] ?? [])
        );

        $this->markPaymentVerified($sale, $data, [], $groupById);

        return $sale->refresh();
    }

    public function duplicateMessage(?string $customerMobile, ?string $journeyDate): array
    {
        if (empty($customerMobile) || empty($journeyDate)) {
            return [
                'exists' => false,
                'message' => null,
            ];
        }

        $existingTicket = ShipTicketSale::where('customer_mobile', $customerMobile)
            ->where('journey_date', $journeyDate)
            ->first();

        return [
            'exists' => $existingTicket !== null,
            'message' => $existingTicket
                ? "This customer already has a ticket for {$journeyDate} on {$existingTicket->sales_source}"
                : null,
        ];
    }

    private function createCoPassengers(ShipTicketSale $ticketSale, array $coPassengers, bool $allowNameOnly): void
    {
        foreach ($coPassengers as $coPassenger) {
            $shouldCreate = $allowNameOnly
                ? ! empty($coPassenger['name'])
                : (! empty($coPassenger['name']) && ! empty($coPassenger['nid']));

            if ($shouldCreate) {
                CoPassenger::create([
                    'ship_ticket_sale_id' => $ticketSale->id,
                    'name' => $coPassenger['name'],
                    'nid' => $coPassenger['nid'] ?? null,
                    'co_passernger_number' => $coPassenger['co_passernger_number'] ?? null,
                    'date_of_birth' => $coPassenger['date_of_birth'] ?? null,
                ]);
            }
        }
    }

    private function createPayments(
        ShipTicketSale $ticketSale,
        array $paymentMethods,
        bool $withExtraFields,
    ): void {
        foreach ($paymentMethods as $paymentMethod) {
            if (! empty($paymentMethod['method']) && ! empty($paymentMethod['amount'])) {
                $isBftn = $paymentMethod['method'] === 'Bank Transfer'
                    && (bool) ($paymentMethod['is_bftn'] ?? false);

                Payment::create([
                    'sales_id' => $ticketSale->id,
                    'payment_method' => $paymentMethod['method'],
                    'is_bftn' => $isBftn,
                    'settlement_status' => $isBftn ? 'pending' : 'received',
                    'received_amount' => $paymentMethod['amount'],
                    'bftn_issue_datetime' => $isBftn
                        ? ($paymentMethod['bftn_issue_datetime'] ?? null)
                        : null,
                    ...($withExtraFields ? [
                        'transaction_id' => $paymentMethod['transaction_id'] ?? null,
                        'payment_datetime' => $paymentMethod['payment_datetime'] ?? null,
                        'payment_proof' => $this->paymentProofs->store($paymentMethod['proof_file'] ?? null),
                    ] : []),
                    'paid_date' => $paymentMethod['paid_date'],
                    ...($withExtraFields ? ['remark' => $paymentMethod['remark'] ?? null] : []),
                ]);
            }
        }
    }

    private function createTicketCategories(ShipTicketSale $ticketSale, array $ticketCategories): void
    {
        $packageIds = collect($ticketCategories)
            ->flatMap(fn (mixed $categories): array => is_array($categories) ? $categories : [])
            ->pluck('package_id')
            ->map(fn (mixed $packageId): int => (int) $packageId)
            ->unique();
        $packages = ShipPackage::query()
            ->where('ship_id', $ticketSale->ship_id)
            ->whereIn('id', $packageIds)
            ->get()
            ->keyBy('id');

        foreach ($ticketCategories as $type => $categories) {
            foreach ($categories as $category) {
                if ($category['quantity'] > 0) {
                    $package = $packages->get((int) $category['package_id']);

                    if (! $package) {
                        throw ValidationException::withMessages([
                            'ticket_categories' => 'One or more selected ticket packages are invalid for this ship.',
                        ]);
                    }

                    Category::create([
                        'ticket_id' => $ticketSale->id,
                        'package_id' => $category['package_id'],
                        'quantity' => $category['quantity'],
                        'type' => $type,
                        'unit_price' => $this->saleFinancials->categoryUnitPrice(
                            $package,
                            $type,
                            $ticketSale->return_date,
                        ),
                    ]);
                }
            }
        }
    }

    private function updatePackageCategories(ShipTicketSale $sale, array $data): void
    {
        $sale->categories()->delete();
        $packageIds = array_unique(array_merge(
            array_map('intval', array_keys($data['departure_quantity'] ?? [])),
            array_map('intval', array_keys($data['return_quantity'] ?? [])),
        ));
        $packages = ShipPackage::query()
            ->where('ship_id', $data['ship_id'] ?? $sale->ship_id)
            ->whereIn('id', $packageIds)
            ->get()
            ->keyBy('id');

        foreach (($data['departure_quantity'] ?? []) as $packageId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $sale->categories()->create([
                    'package_id' => (int) $packageId,
                    'type' => 'departure',
                    'quantity' => $quantity,
                    'unit_price' => $this->saleFinancials->categoryUnitPrice(
                        $packages->get((int) $packageId),
                        'departure',
                        $data['return_date'] ?? $sale->return_date,
                    ),
                ]);
            }
        }

        foreach (($data['return_quantity'] ?? []) as $packageId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $sale->categories()->create([
                    'package_id' => (int) $packageId,
                    'type' => 'return',
                    'quantity' => $quantity,
                    'unit_price' => $this->saleFinancials->categoryUnitPrice(
                        $packages->get((int) $packageId),
                        'return',
                        $data['return_date'] ?? $sale->return_date,
                    ),
                ]);
            }
        }
    }

    private function updatePayments(ShipTicketSale $sale, array $payments): void
    {
        $existingPayments = $sale->payments()->get()->keyBy('id');
        $retainedProofs = [];
        $updatedIds = [];

        foreach ($payments as $payment) {
            if (empty($payment['payment_method']) || empty($payment['received_amount'])) {
                continue;
            }

            $existing = $existingPayments->get((int) ($payment['payment_id'] ?? 0));
            $isBftn = $payment['payment_method'] === 'Bank Transfer'
                && (array_key_exists('is_bftn', $payment) ? (bool) $payment['is_bftn'] : (bool) $existing?->is_bftn);
            $proof = $this->paymentProofs->store($payment['proof_file'] ?? null)
                ?? ($payment['payment_proof'] ?? $existing?->payment_proof);

            if ($proof) {
                $retainedProofs[] = $proof;
            }

            $attributes = [
                'payment_method' => $payment['payment_method'],
                'is_bftn' => $isBftn,
                'settlement_status' => $isBftn
                    ? ($existing?->is_bftn ? $existing->settlement_status : 'pending')
                    : 'received',
                'received_amount' => $payment['received_amount'],
                'paid_date' => $payment['paid_date'] ?? null,
                'transaction_id' => $payment['transaction_id'] ?? null,
                'payment_datetime' => $payment['payment_datetime'] ?? null,
                'bftn_issue_datetime' => $payment['bftn_issue_datetime'] ?? $existing?->bftn_issue_datetime,
                'received_at' => $existing?->received_at,
                'bftn_notification_read_at' => $isBftn && $existing?->is_bftn
                    ? $existing->bftn_notification_read_at
                    : null,
                'payment_proof' => $proof,
                'remark' => $payment['remark'] ?? null,
            ];

            if ($existing) {
                $existing->update($attributes);
                $updatedIds[] = $existing->id;
            } else {
                $created = $sale->payments()->create($attributes);
                $updatedIds[] = $created->id;
            }
        }

        foreach ($existingPayments as $existing) {
            if (! in_array($existing->id, $updatedIds, true)) {
                $existing->delete();
                if ($existing->payment_proof && ! in_array($existing->payment_proof, $retainedProofs, true)) {
                    $this->paymentProofs->delete($existing->payment_proof);
                }
            }
        }
    }

    private function updateCoPassengers(ShipTicketSale $sale, array $coPassengers): void
    {
        $sale->coPassengers()->delete();

        foreach ($coPassengers as $passenger) {
            if (! empty($passenger['name'])) {
                $sale->coPassengers()->create([
                    'name' => $passenger['name'],
                    'nid' => $passenger['nid'] ?? null,
                    'co_passernger_number' => $passenger['co_passernger_number'] ?? null,
                    'date_of_birth' => $passenger['date_of_birth'] ?? null,
                ]);
            }
        }
    }

    private function markPaymentVerified(ShipTicketSale $sale, array $data, array $input, ?int $groupByIdOverride = null): void
    {
        DB::transaction(function () use ($sale, $data, $input, $groupByIdOverride): void {
            $groupById = ($data['group_tickets'] ?? null) === 'yes' && ! empty($data['group_by_id'])
                ? (int) $data['group_by_id']
                : ($groupByIdOverride ?? $sale->id);

            if (! empty($input['pdf']) && is_array($input['pdf'])) {
                $pdfValues = $input['pdf'];
            } else {
                $pdfValues = $data['pdf'] ?? [];
            }

            foreach ($pdfValues as $pdfValue) {
                $filename = trim((string) $pdfValue);

                if ($filename === '') {
                    continue;
                }

                if (! str_ends_with(strtolower($filename), '.pdf')) {
                    $filename .= '.pdf';
                }

                if ($sale->printedTickets()->where('filename', $filename)->exists()) {
                    continue;
                }

                $sale->printedTickets()->create([
                    'filename' => $filename,
                    'group_by_id' => $groupById,
                ]);
            }

            if ($sale->status !== SaleStatus::PaymentVerified->value) {
                return;
            }

            $sale->update(['status' => SaleStatus::TicketIssued->value]);
            VerifyTracker::create([
                'name' => SaleStatus::TicketIssued->value,
                'ticket_id' => $sale->id,
                'verified_by' => auth()->id(),
            ]);
        });
    }

    private function addPrintedTickets(ShipTicketSale $sale, mixed $filenames): void
    {
        if (! is_array($filenames)) {
            return;
        }

        $groupById = $sale->printedTickets()->latest('id')->value('group_by_id') ?? $sale->id;

        foreach ($filenames as $filename) {
            $filename = trim((string) $filename);

            if ($filename === '') {
                continue;
            }

            if (! str_ends_with(strtolower($filename), '.pdf')) {
                $filename .= '.pdf';
            }

            if ($sale->printedTickets()->where('filename', $filename)->exists()) {
                continue;
            }

            $sale->printedTickets()->create([
                'filename' => $filename,
                'group_by_id' => $groupById,
            ]);
        }
    }

    private function appendAdminSaleToSheet(ShipTicketSale $ticketSale, array $data, array $input): void
    {
        $ship = Ship::find($input['ship_id'] ?? null);
        $companyName = $ticketSale->companies()->value('name') ?? '';
        $user = User::find($input['sold_by'] ?? null);

        try {
            GoogleSheetService::appendRow([
                $data['customer_name'],
                $data['customer_mobile'],
                $data['whatsapp'] ?? $data['whatsapp_username'] ?? $data['customer_mobile'],
                $data['email'] ?? '',
                $ship?->name ?? '',
                $companyName,
                $ticketSale->sales_source,
                $data['ticket_value'],
                $data['received_amount'],
                $this->paymentString($input['payment_methods'] ?? []),
                $user?->name ?? '',
                now()->format('Y-m-d'),
                $input['address'] ?? null,
                $input['remark1'] ?? null,
                $input['remark2'] ?? null,
                $data['discount_amount'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('GoogleSheet append failed for sale: '.($ticketSale->id ?? 'unknown').' | '.$e->getMessage());
        }
    }

    private function appendPublicSaleToSheet(array $data, array $input, ?WhatsappDetail $whatsapp): void
    {
        $ship = Ship::find($input['ship_id'] ?? null);

        GoogleSheetService::appendRow([
            $data['customer_name'],
            $data['customer_mobile'],
            $data['whatsapp'] ?? $data['whatsapp_username'] ?? $data['customer_mobile'],
            $data['email'] ?? '',
            $ship->name,
            $whatsapp->whatsapp_number ?? 'not found',
            $data['ticket_value'],
            $data['received_amount'],
            $this->paymentString($input['payment_methods'] ?? []),
            'guest',
            now()->format('Y-m-d'),
            $input['address'] ?? null,
            $input['remark1'] ?? null,
            $input['remark2'] ?? null,
        ]);
    }

    private function paymentString(array $paymentMethods): string
    {
        $payments = [];

        foreach ($paymentMethods as $paymentMethod) {
            if (! empty($paymentMethod['method']) && ! empty($paymentMethod['amount'])) {
                $payments[] = $paymentMethod['method'].'='.$paymentMethod['amount'];
            }
        }

        return implode(', ', $payments);
    }

    private function normalizeFinancials(array $data, array $input, bool $publicPricing = false): array
    {
        $this->validateBftnPaymentInput($input);

        if (array_key_exists('ticket_categories', $input) && is_array($input['ticket_categories'])) {
            $data['ticket_value'] = $this->saleFinancials->ticketValueFromCategories(
                $data['ship_id'],
                $data['return_date'] ?? null,
                $input['ticket_categories'],
                $publicPricing,
            );
        } elseif (! isset($data['ticket_value'])) {
            $data['ticket_value'] = 0.0;
        }

        $data['other_fee'] = (float) ($data['other_fee'] ?? $input['other_fee'] ?? 0);
        $data['discount_amount'] = (float) ($data['discount_amount'] ?? $input['discount_amount'] ?? 0);
        $receivedAmount = $this->receivedAmountFromPaymentInput($input)
            ?? 0.0;
        $pendingBftnAmount = $this->pendingBftnAmountFromPaymentInput($input);
        $summary = $this->saleFinancials->summary(
            (float) ($data['ticket_value'] ?? 0),
            $data['other_fee'],
            $data['discount_amount'],
            $receivedAmount,
            $pendingBftnAmount,
        );

        return [...$data, ...$summary, 'other_fee' => $data['other_fee'], 'discount_amount' => $data['discount_amount']];
    }

    private function receivedAmountFromPaymentInput(array $input): ?float
    {
        $paymentField = array_key_exists('payment_methods', $input) ? 'payment_methods' : 'payments';
        $payments = $input[$paymentField] ?? null;

        if (! is_array($payments)) {
            return null;
        }

        $amountKey = $paymentField === 'payment_methods' ? 'amount' : 'received_amount';

        return round(array_reduce(
            $payments,
            fn (float $total, mixed $payment): float => $total + (
                is_array($payment)
                    ? $this->settledPaymentInputAmount($payment, $amountKey)
                    : 0
            ),
            0.0,
        ), 2);
    }

    private function pendingBftnAmountFromPaymentInput(array $input): float
    {
        $paymentField = array_key_exists('payment_methods', $input) ? 'payment_methods' : 'payments';
        $amountKey = $paymentField === 'payment_methods' ? 'amount' : 'received_amount';

        return round(array_reduce(
            $input[$paymentField] ?? [],
            fn (float $total, mixed $payment): float => $total + (
                is_array($payment)
                && ! empty($payment['is_bftn'])
                && ($payment['settlement_status'] ?? null) !== 'received'
                    ? max((float) ($payment[$amountKey] ?? 0), 0)
                    : 0
            ),
            0.0,
        ), 2);
    }

    private function settledPaymentInputAmount(array $payment, string $amountKey): float
    {
        $isBftn = (bool) ($payment['is_bftn'] ?? false)
            && ($payment['method'] ?? $payment['payment_method'] ?? null) === 'Bank Transfer';
        $isSettled = ($payment['settlement_status'] ?? null) === 'received';

        return $isBftn && ! $isSettled ? 0.0 : max((float) ($payment[$amountKey] ?? 0), 0);
    }

    private function validateBftnPaymentInput(array $input): void
    {
        $field = array_key_exists('payment_methods', $input) ? 'payment_methods' : 'payments';
        $payments = $input[$field] ?? [];
        foreach ($payments as $index => $payment) {
            if (! is_array($payment) || empty($payment['is_bftn'])) {
                continue;
            }

            if (($payment['method'] ?? $payment['payment_method'] ?? null) !== 'Bank Transfer') {
                throw ValidationException::withMessages([
                    $field.'.'.$index.'.is_bftn' => 'Only a Bank Transfer payment can be marked as BFTN.',
                ]);
            }
        }
    }
}
