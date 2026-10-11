<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->recalculateDueAmounts(true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->recalculateDueAmounts(false);
    }

    private function recalculateDueAmounts(bool $excludePendingBftn): void
    {
        DB::table('ship_ticket_sales')
            ->select(['id', 'total_payable', 'received_amount'])
            ->orderBy('id')
            ->chunkById(500, function ($sales) use ($excludePendingBftn): void {
                $saleIds = $sales->pluck('id');
                $payments = DB::table('payments')
                    ->whereIn('sales_id', $saleIds)
                    ->selectRaw('
                        sales_id,
                        COUNT(*) AS payment_count,
                        SUM(CASE WHEN is_bftn = 0 OR settlement_status = "received" THEN received_amount ELSE 0 END) AS settled_amount,
                        SUM(CASE WHEN is_bftn = 1 AND settlement_status = "pending" THEN received_amount ELSE 0 END) AS pending_bftn_amount
                    ')
                    ->groupBy('sales_id')
                    ->get()
                    ->keyBy('sales_id');
                $dueAdjustments = DB::table('refunds')
                    ->whereIn('sales_id', $saleIds)
                    ->where(function ($query): void {
                        $query->where('status', 'completed')
                            ->orWhereNotNull('customer_refunded_at');
                    })
                    ->selectRaw('sales_id, SUM(due_adjusted_amount) AS amount')
                    ->groupBy('sales_id')
                    ->pluck('amount', 'sales_id');

                foreach ($sales as $sale) {
                    $paymentSummary = $payments->get($sale->id);
                    $receivedAmount = $paymentSummary && $paymentSummary->payment_count > 0
                        ? (float) $paymentSummary->settled_amount
                        : (float) $sale->received_amount;
                    $pendingBftnAmount = $excludePendingBftn
                        ? (float) ($paymentSummary->pending_bftn_amount ?? 0)
                        : 0.0;
                    $dueAdjustment = (float) ($dueAdjustments[$sale->id] ?? 0);
                    $dueAmount = max(
                        (float) $sale->total_payable - $receivedAmount - $pendingBftnAmount - $dueAdjustment,
                        0,
                    );

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update(['due_amount' => round($dueAmount, 2)]);
                }
            });
    }
};
