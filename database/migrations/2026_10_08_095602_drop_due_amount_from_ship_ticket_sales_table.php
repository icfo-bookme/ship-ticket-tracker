<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->dropColumn('due_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->decimal('due_amount', 10, 2)->default(0);
        });

        $totalPayable = 'COALESCE(ship_ticket_sales.total_payable, 0)';
        $settledPayments = '(SELECT COALESCE(SUM(CASE WHEN payments.is_bftn = 0 OR payments.settlement_status = "received" THEN payments.received_amount ELSE 0 END), 0) FROM payments WHERE payments.sales_id = ship_ticket_sales.id)';
        $pendingBftnPayments = '(SELECT COALESCE(SUM(received_amount), 0) FROM payments WHERE payments.sales_id = ship_ticket_sales.id AND payments.is_bftn = 1 AND payments.settlement_status = "pending")';
        $refundDueAdjustments = '(SELECT COALESCE(SUM(due_adjusted_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))';
        $dueAmount = "{$totalPayable} - {$settledPayments} - {$pendingBftnPayments} - {$refundDueAdjustments}";
        $dueAmountSql = "CASE WHEN {$dueAmount} > 0 THEN {$dueAmount} ELSE 0 END";

        DB::table('ship_ticket_sales')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($sales) use ($dueAmountSql): void {
                foreach ($sales as $sale) {
                    $dueAmount = DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->selectRaw("{$dueAmountSql} AS amount")
                        ->value('amount');

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update(['due_amount' => round((float) $dueAmount, 2)]);
                }
            });
    }
};
