<?php

namespace App\Services\Finance;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\ShipTicketSale;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraPaymentService
{
    public function __construct(private readonly AuditLogService $auditLogs) {}

    /**
     * @return array{draw: mixed, recordsTotal: int, recordsFiltered: int, data: array}
     */
    public function dataTable(Request $request): array
    {
        $query = ShipTicketSale::with(['ships', 'companies', 'refunds', 'payments'])
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN payments.is_bftn = 0 OR payments.settlement_status = "received" THEN payments.received_amount ELSE 0 END), 0) FROM payments WHERE payments.sales_id = ship_ticket_sales.id) > ship_ticket_sales.total_payable')
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
                'received_amount' => $this->settledAmount($sale),
                'extra_remaining_amount' => $this->remainingAmount($sale),
            ])->all(),
        ];
    }

    /** @return array{success: bool, amount: float|null} */
    public function adjustToOtherFee(int $saleId): array
    {
        return $this->auditLogs->capture(ShipTicketSale::findOrFail($saleId), fn (ShipTicketSale $sale): array => $this->performAdjustToOtherFee($sale->id));
    }

    private function performAdjustToOtherFee(int $saleId): array
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

            $refund = Refund::create([
                'sales_id' => $sale->id,
                'refund_type' => 'extra_payment',
                'reason' => 'Extra received amount',
                'refunded_number_of_tickets' => 0,
                'refunded_amount' => $extra,
                'gross_refund_amount' => $extra,
                'customer_refund_amount' => $extra,
                'status' => RefundStatus::Requested->value,
                'requested_at' => now(),
            ]);
            $this->auditLogs->record($refund, 'created', newValues: $this->auditLogs->snapshot($refund));

            return [
                'refund' => $refund,
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

        $extraReceived = max($this->settledAmount($sale) - (float) $sale->total_payable, 0);
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
                $extraRefundPending += max((float) $refund->gross_refund_amount - $paidAmount, 0);
            }
        }

        $reservedAmount = (float) $extraRefunds->sum('gross_refund_amount');

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

    private function settledAmount(ShipTicketSale $sale): float
    {
        $sale->loadMissing('payments');

        return (float) $sale->payments
            ->reject(fn ($payment): bool => $payment->is_bftn && $payment->settlement_status !== 'received')
            ->sum('received_amount');
    }
}
