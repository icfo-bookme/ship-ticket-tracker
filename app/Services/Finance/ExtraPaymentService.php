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
        $extraReceived = max((float) $sale->received_amount - (float) $sale->total_payable, 0);
        $refunds = $sale->relationLoaded('refunds') ? $sale->refunds : $sale->refunds()->get();
        $reservedOrRefunded = (float) $refunds
            ->where('refund_type', 'extra_payment')
            ->where('status', '!=', RefundStatus::Cancelled->value)
            ->sum('refunded_amount');

        return max(round($extraReceived - $reservedOrRefunded, 2), 0);
    }

    private function hasActiveExtraRefund(ShipTicketSale $sale): bool
    {
        return $sale->refunds
            ->contains(fn (Refund $refund): bool => $refund->refund_type === 'extra_payment'
                && $refund->status !== RefundStatus::Cancelled->value);
    }
}
