<?php

namespace App\Services\Refunds;

use App\Enums\SaleStatus;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function create(array $data): Refund
    {
        return Refund::create(array_merge([
            'status' => 'requested',
            'refund_type' => 'partial',
        ], $data));
    }

    public function fullRefund(array $saleIds): void
    {
        DB::transaction(function () use ($saleIds): void {
            foreach ($saleIds as $id) {
                $sale = ShipTicketSale::find($id);

                if ($sale && ! in_array($sale->status, [SaleStatus::Pending->value, SaleStatus::Refunded->value], true)) {
                    $this->ensureRefundableAmount($sale, (int) $sale->number_of_ticket, (float) $sale->ticket_fee);
                    Refund::create([
                        'sales_id' => $sale->id,
                        'refund_type' => 'bulk',
                        'status' => 'customer_refund_pending',
                        'refunded_number_of_tickets' => $sale->number_of_ticket,
                        'refunded_amount' => $sale->ticket_fee,
                        'gross_refund_amount' => $sale->ticket_fee,
                        'customer_refund_amount' => $sale->ticket_fee,
                        'requested_at' => now(),
                    ]);

                    $sale->status = SaleStatus::Refunded->value;
                    $sale->save();
                }
            }
        });
    }

    public function partialRefund(ShipTicketSale $sale, array $data): void
    {
        DB::transaction(function () use ($sale, $data): void {
            $categories = $sale->categories()->with('package')->get()->keyBy('id');
            $grossAmount = 0;
            $ticketCount = 0;
            $selectedTickets = [];

            foreach ($data['ticket_selections'] as $selection) {
                $category = $categories->get((int) $selection['category_id']);

                if (! $category || (int) $selection['refunded_quantity'] > (int) $category->quantity) {
                    throw ValidationException::withMessages([
                        'ticket_selections' => 'A return quantity cannot exceed the purchased category quantity.',
                    ]);
                }

                $quantity = (int) $selection['refunded_quantity'];
                $unitAmount = (float) ($category->type === 'return'
                    ? ($category->package?->round_trip_price ?? $category->package?->price ?? 0)
                    : ($category->package?->price ?? 0));
                $categoryAmount = round($unitAmount * $quantity, 2);
                $grossAmount += $categoryAmount;
                $ticketCount += $quantity;
                $selectedTickets[] = compact('category', 'quantity', 'unitAmount', 'categoryAmount');
            }

            $this->ensureRefundableCategories($sale, $selectedTickets, $grossAmount);

            $customerChargePercent = (float) $data['customer_charge_percent'];
            $partnerSharePercent = (float) $data['partner_share_percent'];
            $customerChargeAmount = round($grossAmount * $customerChargePercent / 100, 2);
            $partnerShareAmount = round($grossAmount * $partnerSharePercent / 100, 2);

            Refund::create([
                'sales_id' => $sale->id,
                'refund_type' => 'partial',
                'status' => 'sent_to_partner',
                'refunded_number_of_tickets' => $ticketCount,
                'refunded_amount' => $grossAmount,
                'gross_refund_amount' => $grossAmount,
                'customer_charge_percent' => $customerChargePercent,
                'customer_charge_amount' => $customerChargeAmount,
                'partner_share_percent' => $partnerSharePercent,
                'partner_share_amount' => $partnerShareAmount,
                'customer_refund_amount' => round($grossAmount - $customerChargeAmount, 2),
                'company_retained_amount' => round($customerChargeAmount - $partnerShareAmount, 2),
                'requested_at' => now(),
                'remark' => $data['remark'] ?? null,
            ])->tickets()->createMany(array_map(fn (array $selected): array => [
                'category_id' => $selected['category']->id,
                'category_name' => $selected['category']->package?->name,
                'category_type' => $selected['category']->type,
                'purchased_quantity' => $selected['category']->quantity,
                'refunded_quantity' => $selected['quantity'],
                'unit_amount' => $selected['unitAmount'],
                'gross_amount' => $selected['categoryAmount'],
            ], $selectedTickets));
        });
    }

    public function receivePartnerPayment(Refund $refund, array $data): void
    {
        DB::transaction(function () use ($refund, $data): void {
            $refund->partnerPayments()->create([
                'requested_amount' => $refund->partner_share_amount,
                'received_amount' => $data['received_amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'payment_proof' => $data['payment_proof'] ?? null,
                'received_at' => now(),
                'status' => 'received',
                'remark' => $data['remark'] ?? null,
            ]);
            $refund->update(['status' => 'customer_refund_pending', 'partner_received_at' => now()]);
        });
    }

    public function refundCustomer(Refund $refund, array $data): void
    {
        DB::transaction(function () use ($refund, $data): void {
            $refund->customerPayments()->create([
                'amount' => $refund->customer_refund_amount,
                'payment_method' => $data['payment_method'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'payment_proof' => $data['payment_proof'] ?? null,
                'paid_at' => now(),
                'status' => 'paid',
                'remark' => $data['remark'] ?? null,
            ]);
            $refund->update(['status' => 'completed', 'customer_refunded_at' => now()]);
            $this->refreshSaleStatus($refund->sale);
        });
    }

    public function update(Refund $refund, ShipTicketSale $sale, array $data): void
    {
        if (in_array($refund->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'This refund request can no longer be edited.']);
        }

        DB::transaction(function () use ($refund, $sale, $data): void {
            $categories = $sale->categories()->with('package')->get()->keyBy('id');
            $grossAmount = 0;
            $ticketCount = 0;
            $selectedTickets = [];

            foreach ($data['ticket_selections'] as $selection) {
                $category = $categories->get((int) $selection['category_id']);
                $quantity = (int) $selection['refunded_quantity'];

                if (! $category || $quantity > (int) $category->quantity) {
                    throw ValidationException::withMessages([
                        'ticket_selections' => 'A return quantity cannot exceed the purchased category quantity.',
                    ]);
                }

                $unitAmount = (float) ($category->type === 'return'
                    ? ($category->package?->round_trip_price ?? $category->package?->price ?? 0)
                    : ($category->package?->price ?? 0));
                $categoryAmount = round($unitAmount * $quantity, 2);
                $grossAmount += $categoryAmount;
                $ticketCount += $quantity;
                $selectedTickets[] = compact('category', 'quantity', 'unitAmount', 'categoryAmount');
            }

            $this->ensureRefundableCategories($sale, $selectedTickets, $grossAmount, $refund->id);
            $customerChargePercent = (float) $data['customer_charge_percent'];
            $partnerSharePercent = (float) $data['partner_share_percent'];
            $customerChargeAmount = round($grossAmount * $customerChargePercent / 100, 2);
            $partnerShareAmount = round($grossAmount * $partnerSharePercent / 100, 2);

            $refund->update([
                'refunded_number_of_tickets' => $ticketCount,
                'refunded_amount' => $grossAmount,
                'gross_refund_amount' => $grossAmount,
                'customer_charge_percent' => $customerChargePercent,
                'customer_charge_amount' => $customerChargeAmount,
                'partner_share_percent' => $partnerSharePercent,
                'partner_share_amount' => $partnerShareAmount,
                'customer_refund_amount' => round($grossAmount - $customerChargeAmount, 2),
                'company_retained_amount' => round($customerChargeAmount - $partnerShareAmount, 2),
                'remark' => $data['remark'] ?? null,
            ]);
            $refund->tickets()->delete();
            $refund->tickets()->createMany(array_map(fn (array $selected): array => [
                'category_id' => $selected['category']->id,
                'category_name' => $selected['category']->package?->name,
                'category_type' => $selected['category']->type,
                'purchased_quantity' => $selected['category']->quantity,
                'refunded_quantity' => $selected['quantity'],
                'unit_amount' => $selected['unitAmount'],
                'gross_amount' => $selected['categoryAmount'],
            ], $selectedTickets));
        });
    }

    public function cancel(Refund $refund): void
    {
        if (in_array($refund->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'This refund request cannot be cancelled.']);
        }

        $refund->update(['status' => 'cancelled']);
    }

    private function ensureRefundableAmount(
        ShipTicketSale $sale,
        int $tickets,
        float $amount,
        ?int $ignoredRefundId = null,
    ): void {
        $refunds = $sale->refunds()
            ->when($ignoredRefundId, fn ($query) => $query->where('id', '!=', $ignoredRefundId))
            ->get();
        $refundedTickets = (int) $refunds->sum('refunded_number_of_tickets');
        $refundedAmount = (float) $refunds->sum('refunded_amount');

        if ($refundedTickets + $tickets > (int) $sale->number_of_ticket) {
            throw ValidationException::withMessages([
                'refunded_number_of_tickets' => 'Refunded tickets cannot exceed the sale ticket count.',
            ]);
        }

        if ($refundedAmount + $amount > (float) $sale->ticket_fee) {
            throw ValidationException::withMessages([
                'refunded_amount' => 'Refunded amount cannot exceed the total ticket price.',
            ]);
        }

    }

    private function ensureRefundableCategories(ShipTicketSale $sale, array $selectedTickets, float $amount, ?int $ignoredRefundId = null): void
    {
        $refunds = $sale->refunds()
            ->when($ignoredRefundId, fn ($query) => $query->where('id', '!=', $ignoredRefundId))
            ->with('tickets')
            ->get();
        $refundedAmount = (float) $refunds->sum('refunded_amount');

        foreach ($selectedTickets as $selected) {
            $alreadyRefunded = (int) $refunds
                ->flatMap->tickets
                ->where('category_id', $selected['category']->id)
                ->sum('refunded_quantity');

            if ($alreadyRefunded + $selected['quantity'] > (int) $selected['category']->quantity) {
                throw ValidationException::withMessages([
                    'ticket_selections' => 'A category refund cannot exceed its remaining ticket quantity.',
                ]);
            }
        }

        if ($refundedAmount + $amount > (float) $sale->ticket_fee) {
            throw ValidationException::withMessages([
                'refunded_amount' => 'Refunded amount cannot exceed the total ticket price.',
            ]);
        }
    }

    private function refreshSaleStatus(ShipTicketSale $sale): void
    {
        $refundedTickets = (int) $sale->refunds()->sum('refunded_number_of_tickets');
        $sale->status = $refundedTickets >= (int) $sale->number_of_ticket
            ? SaleStatus::Refunded->value
            : SaleStatus::PartialRefunded->value;
        $sale->save();
    }
}
