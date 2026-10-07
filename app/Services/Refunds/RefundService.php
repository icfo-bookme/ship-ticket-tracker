<?php

namespace App\Services\Refunds;

use App\Enums\RefundStatus;
use App\Enums\SaleStatus;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private readonly SaleFinancialService $saleFinancials) {}

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
                $sale = ShipTicketSale::query()
                    ->with('categories.package')
                    ->find($id);

                if ($sale && ! in_array($sale->status, [SaleStatus::Pending->value, SaleStatus::Refunded->value], true)) {
                    $ticketSelections = $sale->categories->map(function ($category): array {
                        $singlePrice = (float) ($category->package?->price ?? 0);
                        $roundTripPrice = (float) ($category->package?->round_trip_price ?? 0);
                        $unitAmount = $category->type === 'return'
                            ? ($roundTripPrice > 0 ? $roundTripPrice - $singlePrice : $singlePrice)
                            : $singlePrice;
                        $quantity = (int) $category->quantity;

                        return [
                            'category' => $category,
                            'quantity' => $quantity,
                            'unitAmount' => $unitAmount,
                            'categoryAmount' => round($unitAmount * $quantity, 2),
                        ];
                    })->all();
                    $ticketCount = array_sum(array_column($ticketSelections, 'quantity'));
                    $ticketCount = $ticketCount > 0 ? $ticketCount : (int) $sale->number_of_ticket;
                    $grossAmount = round((float) $sale->ticket_fee, 2);
                    $this->ensureRefundableAmount($sale, $ticketCount, $grossAmount);
                    $refundDiscountAmount = min(max((float) $sale->discount_amount, 0), $grossAmount);
                    $customerRefundAmount = $this->customerRefundAmount(
                        $grossAmount,
                        0,
                        $refundDiscountAmount,
                    );

                    $refund = Refund::create([
                        'sales_id' => $sale->id,
                        'refund_type' => 'bulk',
                        'status' => RefundStatus::Requested->value,
                        'refunded_number_of_tickets' => $ticketCount,
                        'refunded_amount' => $grossAmount,
                        'gross_refund_amount' => $grossAmount,
                        'refund_discount_amount' => $refundDiscountAmount,
                        'other_fee_deduction' => 0,
                        'customer_charge_percent' => 0,
                        'customer_charge_amount' => 0,
                        'partner_share_percent' => 0,
                        'partner_share_amount' => 0,
                        'company_retained_amount' => 0,
                        'customer_refund_amount' => $customerRefundAmount,
                        'requested_at' => now(),
                    ]);

                    if ($ticketSelections === []) {
                        $refund->tickets()->create([
                            'category_name' => 'All tickets',
                            'category_type' => 'all',
                            'purchased_quantity' => $ticketCount,
                            'refunded_quantity' => $ticketCount,
                            'unit_amount' => $ticketCount > 0 ? round($grossAmount / $ticketCount, 2) : 0,
                            'gross_amount' => $grossAmount,
                        ]);
                    } else {
                        $refund->tickets()->createMany(array_map(fn (array $selected): array => [
                            'category_id' => $selected['category']->id,
                            'category_name' => $selected['category']->package?->name,
                            'category_type' => $selected['category']->type,
                            'purchased_quantity' => $selected['quantity'],
                            'refunded_quantity' => $selected['quantity'],
                            'unit_amount' => $selected['unitAmount'],
                            'gross_amount' => $selected['categoryAmount'],
                        ], $ticketSelections));
                    }
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
                        'ticket_selections' => 'Refund quantity cannot exceed the purchased quantity for that category.',
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
            $customerRefundAmount = $this->customerRefundAmount(
                $grossAmount,
                $customerChargeAmount,
                $refundDiscountAmount,
            );

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
                'other_fee_deduction' => 0,
                'customer_refund_amount' => $customerRefundAmount,
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
            $currentSummary = $this->saleFinancials->currentSummary($sale);
            $customerRefundAmount = (float) $refund->customer_refund_amount;
            if ($customerRefundAmount < 0) {
                throw ValidationException::withMessages([
                    'customer_refund_amount' => 'This refund has a negative calculated amount and must be corrected before completion.',
                ]);
            }

            $dueAmount = $currentSummary['due_amount'];
            $dueAdjustment = min($dueAmount, max($customerRefundAmount, 0));
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

    private function customerRefundAmount(
        float $grossAmount,
        float $customerChargeAmount,
        float $refundDiscountAmount,
    ): float {
        $customerRefundAmount = round(
            $grossAmount - $customerChargeAmount - $refundDiscountAmount,
            2,
        );

        if ($customerRefundAmount < 0) {
            throw ValidationException::withMessages([
                'customer_charge_percent' => 'Refund deductions cannot exceed the gross refund amount.',
            ]);
        }

        return $customerRefundAmount;
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
                        'ticket_selections' => 'Refund quantity cannot exceed the purchased quantity for that category.',
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
            $customerRefundAmount = $this->customerRefundAmount(
                $grossAmount,
                $customerChargeAmount,
                $refundDiscountAmount,
            );

            $refund->update([
                'refunded_number_of_tickets' => $ticketCount,
                'refunded_amount' => $grossAmount,
                'gross_refund_amount' => $grossAmount,
                'customer_charge_percent' => $customerChargePercent,
                'customer_charge_amount' => $customerChargeAmount,
                'partner_share_percent' => $partnerSharePercent,
                'partner_share_amount' => $partnerShareAmount,
                'refund_discount_amount' => $refundDiscountAmount,
                'other_fee_deduction' => 0,
                'customer_refund_amount' => $customerRefundAmount,
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

    public function delete(Refund $refund): void
    {
        $refund->delete();
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
