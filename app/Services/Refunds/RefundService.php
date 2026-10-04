<?php

namespace App\Services\Refunds;

use App\Enums\RefundStatus;
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
            'status' => RefundStatus::Requested->value,
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
                        'status' => RefundStatus::Requested->value,
                        'refunded_number_of_tickets' => $sale->number_of_ticket,
                        'refunded_amount' => $sale->ticket_fee,
                        'gross_refund_amount' => $sale->ticket_fee,
                        'refund_discount_amount' => min((float) $sale->discount_amount, (float) $sale->ticket_fee),
                        'customer_refund_amount' => round((float) $sale->ticket_fee - min((float) $sale->discount_amount, (float) $sale->ticket_fee), 2),
                        'requested_at' => now(),
                    ]);

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
                $singlePrice = (float) ($category->package?->price ?? 0);
                $roundTripPrice = (float) ($category->package?->round_trip_price ?? 0);
                $unitAmount = $category->type === 'return'
                    ? ($roundTripPrice > 0 ? $roundTripPrice - $singlePrice : $singlePrice)
                    : $singlePrice;
                $categoryAmount = round($unitAmount * $quantity, 2);
                $grossAmount += $categoryAmount;
                $ticketCount += $quantity;
                $selectedTickets[] = compact('category', 'quantity', 'unitAmount', 'categoryAmount');
            }

            $refundDiscountAmount = $this->proportionalDiscount($sale, $grossAmount);
            $this->ensureRefundableCategories($sale, $selectedTickets, $grossAmount);

            $customerChargePercent = (float) $data['customer_charge_percent'];
            $partnerSharePercent = (float) $data['partner_share_percent'];

            if ($partnerSharePercent > $customerChargePercent) {
                throw ValidationException::withMessages([
                    'partner_share_percent' => 'Partner share cannot exceed customer charge.',
                ]);
            }
            $customerChargeAmount = round($grossAmount * $customerChargePercent / 100, 2);
            $partnerShareAmount = round($grossAmount * $partnerSharePercent / 100, 2);

            Refund::create([
                'sales_id' => $sale->id,
                'refund_type' => 'partial',
                'status' => RefundStatus::Requested->value,
                'refunded_number_of_tickets' => $ticketCount,
                'refunded_amount' => $grossAmount,
                'gross_refund_amount' => $grossAmount,
                'customer_charge_percent' => $customerChargePercent,
                'customer_charge_amount' => $customerChargeAmount,
                'partner_share_percent' => $partnerSharePercent,
                'partner_share_amount' => $partnerShareAmount,
                'refund_discount_amount' => $refundDiscountAmount,
                'customer_refund_amount' => round($grossAmount - $customerChargeAmount - $refundDiscountAmount, 2),
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

    public function refundCustomer(Refund $refund, array $data): void
    {
        if (in_array($refund->status, [RefundStatus::Completed->value, RefundStatus::Cancelled->value], true)) {
            throw ValidationException::withMessages(['status' => 'This refund has already been completed or cancelled.']);
        }

        DB::transaction(function () use ($refund, $data): void {
            $refund = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            if (in_array($refund->status, [RefundStatus::Completed->value, RefundStatus::Cancelled->value], true)) {
                throw ValidationException::withMessages(['status' => 'This refund has already been completed or cancelled.']);
            }

            $sale = ShipTicketSale::query()->lockForUpdate()->findOrFail($refund->sales_id);
            $customerRefundAmount = (float) $refund->customer_refund_amount;
            $dueAmount = max((float) $sale->due_amount, 0);
            $dueAdjustment = min($dueAmount, $customerRefundAmount);
            $payableRefundAmount = round($customerRefundAmount - $dueAdjustment, 2);

            if ($dueAdjustment > 0) {
                $sale->update([
                    'due_amount' => round($dueAmount - $dueAdjustment, 2),
                ]);
                $refund->update([
                    'customer_refund_amount' => $payableRefundAmount,
                    'due_adjusted_amount' => $dueAdjustment,
                ]);
            } else {
                $refund->update(['due_adjusted_amount' => 0]);
            }

            if ($payableRefundAmount > 0) {
                $refund->customerPayments()->create([
                    'amount' => $payableRefundAmount,
                    'payment_method' => $data['payment_method'] ?? null,
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'payment_proof' => $data['payment_proof'] ?? null,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'remark' => $data['remark'] ?? null,
                ]);
            }
            $refund->update(['status' => RefundStatus::Completed->value, 'customer_refunded_at' => now()]);
        });
    }

    private function proportionalDiscount(ShipTicketSale $sale, float $grossAmount): float
    {
        $ticketFee = (float) $sale->ticket_fee;
        $discount = min(max((float) $sale->discount_amount, 0), $ticketFee);

        return $ticketFee > 0 ? round($discount * ($grossAmount / $ticketFee), 2) : 0;
    }

    public function approve(Refund $refund): void
    {
        if ($refund->status !== RefundStatus::Requested->value) {
            throw ValidationException::withMessages([
                'status' => 'Only requested refunds can be approved.',
            ]);
        }

        $refund->update([
            'status' => RefundStatus::PartnerApproved->value,
            'partner_received_at' => now(),
        ]);
    }

    public function addPaymentDetails(Refund $refund, string $details): void
    {
        if ($refund->status !== RefundStatus::PartnerApproved->value) {
            throw ValidationException::withMessages([
                'status' => 'Payment details can only be added to partner-approved refunds.',
            ]);
        }

        $refund->update([
            'refund_payment_details' => $details,
            'status' => RefundStatus::PaymentDetailsAdded->value,
        ]);
    }

    public function update(Refund $refund, ShipTicketSale $sale, array $data): void
    {
        if (in_array($refund->status, [RefundStatus::Completed->value, RefundStatus::Cancelled->value], true)) {
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

                $singlePrice = (float) ($category->package?->price ?? 0);
                $roundTripPrice = (float) ($category->package?->round_trip_price ?? 0);
                $unitAmount = $category->type === 'return'
                    ? ($roundTripPrice > 0 ? $roundTripPrice - $singlePrice : $singlePrice)
                    : $singlePrice;
                $categoryAmount = round($unitAmount * $quantity, 2);
                $grossAmount += $categoryAmount;
                $ticketCount += $quantity;
                $selectedTickets[] = compact('category', 'quantity', 'unitAmount', 'categoryAmount');
            }

            $refundDiscountAmount = $this->proportionalDiscount($sale, $grossAmount);
            $this->ensureRefundableCategories($sale, $selectedTickets, $grossAmount, $refund->id);
            $customerChargePercent = (float) $data['customer_charge_percent'];
            $partnerSharePercent = (float) $data['partner_share_percent'];

            if ($partnerSharePercent > $customerChargePercent) {
                throw ValidationException::withMessages([
                    'partner_share_percent' => 'Partner share cannot exceed customer charge.',
                ]);
            }
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
                'refund_discount_amount' => $refundDiscountAmount,
                'customer_refund_amount' => round($grossAmount - $customerChargeAmount - $refundDiscountAmount, 2),
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
        if (in_array($refund->status, [RefundStatus::Completed->value, RefundStatus::Cancelled->value], true)) {
            throw ValidationException::withMessages(['status' => 'This refund request cannot be cancelled.']);
        }

        $refund->update(['status' => RefundStatus::Cancelled->value]);
    }

    private function ensureRefundableAmount(
        ShipTicketSale $sale,
        int $tickets,
        float $amount,
        ?int $ignoredRefundId = null,
    ): void {
        $refunds = $sale->refunds()
            ->whereNotIn('status', [RefundStatus::Cancelled->value])
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
            ->whereNotIn('status', [RefundStatus::Cancelled->value])
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
}
