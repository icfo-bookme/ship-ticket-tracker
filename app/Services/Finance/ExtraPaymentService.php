<?php

namespace App\Services\Finance;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraPaymentService
{
    /**
     * @return array{draw: mixed, recordsTotal: int, recordsFiltered: int, data: array}
     */
    public function dataTable(Request $request): array
    {
        $query = ShipTicketSale::with(['ships', 'companies', 'refunds'])
            ->whereColumn('received_amount', '>', 'total_payable')
            ->whereDoesntHave('refunds', function ($refunds): void {
                $refunds->where('refund_type', 'extra_payment')
                    ->whereNotIn('status', [RefundStatus::Cancelled->value]);
            });

        $total = (clone $query)->count();
        $sales = $query->latest('id')
            ->skip(max(0, $request->integer('start', 0)))
            ->take(min(100, max(1, $request->integer('length', 10))))
            ->get();

        return [
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $sales->map(fn (ShipTicketSale $sale): array => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_mobile' => $sale->customer_mobile,
                'ship_name' => $sale->ships?->name,
                'company_name' => $sale->companies?->name,
                'journey_date' => $sale->journey_date,
                'total_payable' => (float) $sale->total_payable,
                'received_amount' => (float) $sale->received_amount,
                'extra_remaining_amount' => $this->remainingAmount($sale),
            ])->all(),
        ];
    }

    /** @return array{success: bool, amount: float|null} */
    public function adjustToOtherFee(int $saleId): array
    {
        return DB::transaction(function () use ($saleId): array {
            $sale = ShipTicketSale::query()->with('refunds')->lockForUpdate()->findOrFail($saleId);
            $extra = $this->remainingAmount($sale);

            if ($extra <= 0 || $this->hasActiveExtraRefund($sale)) {
                return ['success' => false, 'amount' => null];
            }

            $sale->update([
                'other_fee' => (float) $sale->other_fee + $extra,
                'total_payable' => (float) $sale->total_payable + $extra,
                'due_amount' => 0,
            ]);

            return ['success' => true, 'amount' => $extra];
        });
    }

    /** @return array{refund: Refund|null, message: string|null} */
    public function requestRefund(int $saleId): array
    {
        return DB::transaction(function () use ($saleId): array {
            $sale = ShipTicketSale::query()->with('refunds')->lockForUpdate()->findOrFail($saleId);
            $extra = $this->remainingAmount($sale);

            if ($extra <= 0) {
                return ['refund' => null, 'message' => 'This sale has no extra received amount to refund.'];
            }

            if ($this->hasActiveExtraRefund($sale)) {
                return ['refund' => null, 'message' => 'A refund request already exists for this extra amount.'];
            }

            return [
                'refund' => Refund::create([
                    'sales_id' => $sale->id,
                    'refund_type' => 'extra_payment',
                    'reason' => 'Extra received amount',
                    'refunded_number_of_tickets' => 0,
                    'refunded_amount' => $extra,
                    'gross_refund_amount' => $extra,
                    'customer_refund_amount' => $extra,
                    'status' => RefundStatus::Requested->value,
                    'requested_at' => now(),
                ]),
                'message' => null,
            ];
        });
    }

    public function remainingAmount(ShipTicketSale $sale): float
    {
        return $this->summary($sale)['extra_available_amount'];
    }

    /** @return array{extra_received_amount: float, extra_refunded_amount: float, extra_refund_pending_amount: float, extra_available_amount: float} */
    public function summary(ShipTicketSale $sale): array
    {
        $sale->loadMissing('refunds.customerPayments');

        $extraReceived = max((float) $sale->received_amount - (float) $sale->total_payable, 0);
        $extraRefunds = $sale->refunds
            ->where('refund_type', 'extra_payment')
            ->where('status', '!=', RefundStatus::Cancelled->value);
        $extraRefunded = 0.0;
        $extraRefundPending = 0.0;

        foreach ($extraRefunds as $refund) {
            $paidAmount = (float) $refund->customerPayments
                ->where('status', 'paid')
                ->sum('amount');
            $isSettled = $refund->status === RefundStatus::Completed->value
                || $refund->customer_refunded_at !== null;

            if ($paidAmount === 0.0 && $isSettled) {
                $paidAmount = (float) $refund->customer_refund_amount;
            }

            $extraRefunded += $paidAmount;

            if (! $isSettled) {
                $extraRefundPending += max((float) $refund->refunded_amount - $paidAmount, 0);
            }
        }

        $reservedAmount = (float) $extraRefunds->sum('refunded_amount');

     

        return [
            'extra_received_amount' => round($extraReceived, 2),
            'extra_refunded_amount' => round($extraRefunded, 2),
            'extra_refund_pending_amount' => round($extraRefundPending, 2),
            'extra_available_amount' => round(max($extraReceived - $reservedAmount, 0), 2),
        ];
    }

    private function hasActiveExtraRefund(ShipTicketSale $sale): bool
    {
        return $sale->refunds
            ->contains(fn (Refund $refund): bool => $refund->refund_type === 'extra_payment'
                && $refund->status !== RefundStatus::Cancelled->value);
    }
}
