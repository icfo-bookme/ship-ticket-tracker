<?php

namespace App\Services\Sales;

use App\Models\Bftn;
use App\Models\Category;
use App\Models\CoPassenger;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\WhatsappDetail;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipTicketSaleService
{
    public function create(array $data, array $input): ShipTicketSale
    {
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
        DB::beginTransaction();

        try {
            $sale->update([
                'customer_name' => $data['customer_name'],
                'customer_mobile' => $data['customer_mobile'],
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'],
                'nid' => $data['nid'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'],
                'ship_id' => $data['ship_id'],
                'company_id' => $data['company_id'],
                'journey_date' => $data['journey_date'],
                'return_date' => $data['return_date'],
                'number_of_ticket' => $data['number_of_ticket'],
                'ticket_fee' => $data['ticket_fee'],
                'received_amount' => $data['received_amount'] ?? 0,
                'due_amount' => $data['due_amount'] ?? $data['ticket_fee'],
                'other_fee' => $data['other_fee'] ?? 0,
                'total_payable' => $data['total_payable'] ?? 0,
                'bftn_status' => $data['bftn_status'] ?? null,
                'sales_source' => $data['sales_source'],
                'sold_by' => $data['sold_by'],
                'issued_date' => $data['issued_date'],
                'status' => $data['status'],
                'remark1' => $data['remark1'],
                'remark2' => $data['remark2'],
            ]);

            if (! empty($input['departure_quantity']) || ! empty($input['return_quantity'])) {
                $this->updatePackageCategories($sale, $data);
            }

            $this->updatePayments($sale, $data['payments'] ?? []);
            $this->updateCoPassengers($sale, $data['co_passengers'] ?? []);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        if (($input['status'] ?? null) === 'payment-verified') {
            $this->markPaymentVerified($sale, $data, $input);
        }

        return $sale;
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
                    ] : []),
                    'paid_date' => $paymentMethod['paid_date'],
                    ...($withExtraFields ? ['remark' => $paymentMethod['remark']] : []),
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
        $sale->payments()->delete();

        foreach ($payments as $payment) {
            if (! empty($payment['payment_method']) && ! empty($payment['received_amount'])) {
                $sale->payments()->create([
                    'payment_method' => $payment['payment_method'],
                    'received_amount' => $payment['received_amount'],
                    'paid_date' => $payment['paid_date'] ?? null,
                    'remark' => $payment['remark'] ?? null,
                ]);
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
                    'co_passenger_number' => $passenger['co_passenger_number'] ?? null,
                    'date_of_birth' => $passenger['date_of_birth'] ?? null,
                ]);
            }
        }
    }

    private function markPaymentVerified(ShipTicketSale $sale, array $data, array $input): void
    {
        DB::transaction(function () use ($sale, $data, $input): void {
            if (! empty($input['pdf']) && is_array($input['pdf'])) {
                foreach ($input['pdf'] as $pdfValue) {
                    $sale->printedTickets()->create([
                        'sales_id' => $sale->id,
                        'filename' => $pdfValue.'.pdf',
                        'group_by_id' => ($data['group_tickets'] ?? null) == 'yes'
                            ? $data['group_by_id']
                            : $sale->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (($data['group_tickets'] ?? null) == 'yes' && ! empty($data['group_by_id'])) {
                $groupSale = ShipTicketSale::find($data['group_by_id']);
                $sale->update(['status' => $groupSale?->status ?? 'ticket-issued']);

                return;
            }

            $sale->update(['status' => 'ticket-issued']);
        });
    }

    private function appendAdminSaleToSheet(ShipTicketSale $ticketSale, array $data, array $input): void
    {
        $ship = Ship::find($input['ship_id'] ?? null);
        $user = User::find($input['sold_by'] ?? null);

        try {
            GoogleSheetService::appendRow([
                $data['customer_name'],
                $data['customer_mobile'],
                $data['whatsapp'] ?? $data['customer_mobile'],
                $data['email'] ?? '',
                $ship?->name ?? '',
                $input['sales_source'] ?? null,
                $data['ticket_fee'],
                $input['received_amount'] ?? null,
                $this->paymentString($input['payment_methods'] ?? []),
                $user?->name ?? '',
                now()->format('Y-m-d'),
                $input['address'] ?? null,
                $input['remark1'] ?? null,
                $input['remark2'] ?? null,
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
            $data['whatsapp'] ?? $data['customer_mobile'],
            $data['email'] ?? '',
            $ship->name,
            $whatsapp->whatsapp_number ?? 'not found',
            $data['ticket_fee'],
            $input['received_amount'] ?? null,
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
}
