<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\Bftn;
use App\Models\Category;
use App\Models\CoPassenger;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\WhatsappDetail;
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
    ) {}

    public function delete(int $saleId): void
    {
        ShipTicketSale::findOrFail($saleId)->delete();
    }

    public function create(array $data, array $input): ShipTicketSale
    {
        $data = $this->normalizeFinancials($data, $input);

        $ticketSale = DB::transaction(function () use ($data, $input): ShipTicketSale {
            $ticketSale = ShipTicketSale::create($data);

            $this->createCoPassengers($ticketSale, $input['co_passengers'] ?? [], true);
            $this->createPayments($ticketSale, $input['payment_methods'] ?? [], true);
            $this->createBftnIfNeeded($ticketSale, $input);
            $this->createTicketCategories($ticketSale, $input['ticket_categories'] ?? []);

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

        $ticketSale = DB::transaction(function () use ($data, $input): ShipTicketSale {
            $ticketSale = ShipTicketSale::create($data);

            $this->createCoPassengers($ticketSale, $input['co_passengers'] ?? [], false);
            $this->createPayments($ticketSale, $input['payment_methods'] ?? [], false);
            $this->createTicketCategories($ticketSale, $input['ticket_categories'] ?? []);

            return $ticketSale;
        });

        $this->appendPublicSaleToSheet($data, $input, $whatsapp);

        return $ticketSale;
    }

    public function update(ShipTicketSale $sale, array $data, array $input): ShipTicketSale
    {
        if (array_key_exists('departure_quantity', $input) || array_key_exists('return_quantity', $input)) {
            $ticketCount = array_sum(array_map('intval', $input['departure_quantity'] ?? []))
                + array_sum(array_map('intval', $input['return_quantity'] ?? []));

            if ($ticketCount < 1) {
                throw ValidationException::withMessages([
                    'departure_quantity' => 'At least one ticket category must have a quantity of 1 or more.',
                ]);
            }

            $data['number_of_ticket'] = $ticketCount;
            $data['ticket_fee'] = $this->saleFinancials->ticketFeeFromPackageQuantities(
                $data['ship_id'] ?? $sale->ship_id,
                $data['return_date'] ?? $sale->return_date,
                $input['departure_quantity'] ?? [],
                $input['return_quantity'] ?? [],
                (float) ($data['ticket_fee'] ?? $sale->ticket_fee),
            );
        }
        $data = $this->normalizeFinancials($data, $input);

        DB::beginTransaction();

        try {
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
                'number_of_ticket' => $data['number_of_ticket'],
                'ticket_fee' => $data['ticket_fee'],
                'received_amount' => $data['received_amount'] ?? 0,
                'due_amount' => $data['due_amount'] ?? $data['ticket_fee'],
                'other_fee' => $data['other_fee'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_payable' => $data['total_payable'] ?? 0,
                'bftn_status' => $data['bftn_status'] ?? null,
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

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $sale;
    }

    public function updateIssue(ShipTicketSale $sale, array $data): ShipTicketSale
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

    private function createPayments(ShipTicketSale $ticketSale, array $paymentMethods, bool $withExtraFields): void
    {
        foreach ($paymentMethods as $paymentMethod) {
            if (! empty($paymentMethod['method']) && ! empty($paymentMethod['amount'])) {
                Payment::create([
                    'sales_id' => $ticketSale->id,
                    'payment_method' => $paymentMethod['method'],
                    'received_amount' => $paymentMethod['amount'],
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

    private function createBftnIfNeeded(ShipTicketSale $ticketSale, array $input): void
    {
        if (($input['bftn_status'] ?? null) == 'yes' && ! empty($input['bftn_issue_datetime'])) {
            Bftn::create([
                'sales_id' => $ticketSale->id,
                'bftn_date_time' => $input['bftn_issue_datetime'],
                'status' => 0,
                'notifications_status' => 1,
            ]);
        }
    }

    private function createTicketCategories(ShipTicketSale $ticketSale, array $ticketCategories): void
    {
        foreach ($ticketCategories as $type => $categories) {
            foreach ($categories as $category) {
                if ($category['quantity'] > 0) {
                    Category::create([
                        'ticket_id' => $ticketSale->id,
                        'package_id' => $category['package_id'],
                        'quantity' => $category['quantity'],
                        'type' => $type,
                    ]);
                }
            }
        }
    }

    private function updatePackageCategories(ShipTicketSale $sale, array $data): void
    {
        $sale->categories()->delete();

        foreach (($data['departure_quantity'] ?? []) as $packageId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $sale->categories()->create([
                    'package_id' => (int) $packageId,
                    'type' => 'departure',
                    'quantity' => $quantity,
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
                ]);
            }
        }
    }

    private function updatePayments(ShipTicketSale $sale, array $payments): void
    {
        $existingProofs = $sale->payments()->pluck('payment_proof')->filter()->all();

        $sale->payments()->delete();

        $retainedProofs = [];

        foreach ($payments as $payment) {
            if (empty($payment['payment_method']) || empty($payment['received_amount'])) {
                continue;
            }

            $proof = $this->paymentProofs->store($payment['proof_file'] ?? null) ?? ($payment['payment_proof'] ?? null);

            if ($proof) {
                $retainedProofs[] = $proof;
            }

            $sale->payments()->create([
                'payment_method' => $payment['payment_method'],
                'received_amount' => $payment['received_amount'],
                'paid_date' => $payment['paid_date'] ?? null,
                'transaction_id' => $payment['transaction_id'] ?? null,
                'payment_datetime' => $payment['payment_datetime'] ?? null,
                'payment_proof' => $proof,
                'remark' => $payment['remark'] ?? null,
            ]);
        }

        foreach (array_diff($existingProofs, $retainedProofs) as $removedProof) {
            $this->paymentProofs->delete($removedProof);
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
                    'co_passenger_number' => $passenger['co_passenger_number'] ?? null,
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
        $user = User::find($input['sold_by'] ?? null);

        try {
            GoogleSheetService::appendRow([
                $data['customer_name'],
                $data['customer_mobile'],
                $data['whatsapp'] ?? $data['whatsapp_username'] ?? $data['customer_mobile'],
                $data['email'] ?? '',
                $ship?->name ?? '',
                $input['sales_source'] ?? null,
                $data['ticket_fee'],
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
            $data['ticket_fee'],
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
        if (array_key_exists('ticket_categories', $input) && is_array($input['ticket_categories'])) {
            $data['ticket_fee'] = $this->saleFinancials->ticketFeeFromCategories(
                $data['ship_id'],
                $data['return_date'] ?? null,
                $input['ticket_categories'],
                (float) ($data['ticket_fee'] ?? 0),
                $publicPricing,
            );
        }

        $data['other_fee'] = (float) ($data['other_fee'] ?? $input['other_fee'] ?? 0);
        $data['discount_amount'] = (float) ($data['discount_amount'] ?? $input['discount_amount'] ?? 0);
        $receivedAmount = $this->receivedAmountFromPaymentInput($input)
            ?? (float) ($data['received_amount'] ?? $input['received_amount'] ?? 0);
        $summary = $this->saleFinancials->summary(
            (float) ($data['ticket_fee'] ?? 0),
            $data['other_fee'],
            $data['discount_amount'],
            $receivedAmount,
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
            fn (float $total, mixed $payment): float => $total + (is_array($payment) ? max((float) ($payment[$amountKey] ?? 0), 0) : 0),
            0.0,
        ), 2);
    }
}
