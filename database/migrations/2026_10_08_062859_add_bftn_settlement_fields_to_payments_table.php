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
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_bftn')->default(false)->after('payment_method');
            $table->string('settlement_status', 20)->default('received')->after('is_bftn');
            $table->dateTime('bftn_issue_datetime')->nullable()->after('payment_datetime');
            $table->dateTime('received_at')->nullable()->after('bftn_issue_datetime');
        });

        DB::table('bftn')
            ->join('ship_ticket_sales', 'ship_ticket_sales.id', '=', 'bftn.sales_id')
            ->where('ship_ticket_sales.bftn_status', 'yes')
            ->whereRaw('(SELECT COUNT(*) FROM payments AS bank_payments WHERE bank_payments.sales_id = bftn.sales_id AND bank_payments.payment_method = ?) = 1', ['Bank Transfer'])
            ->select([
                'bftn.sales_id',
                'bftn.bftn_date_time',
                'bftn.received_status',
                'bftn.received_at',
            ])
            ->orderBy('bftn.sales_id')
            ->get()
            ->each(function (object $bftn): void {
                DB::table('payments')
                    ->where('sales_id', $bftn->sales_id)
                    ->where('payment_method', 'Bank Transfer')
                    ->update([
                        'is_bftn' => true,
                        'settlement_status' => $bftn->received_status ? 'received' : 'pending',
                        'bftn_issue_datetime' => $bftn->bftn_date_time,
                        'received_at' => $bftn->received_at,
                    ]);
            });

        DB::table('ship_ticket_sales')
            ->where('bftn_status', 'yes')
            ->select(['id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($sales): void {
                foreach ($sales as $sale) {
                    $legacyBftn = DB::table('bftn')->where('sales_id', $sale->id)->first();
                    $bankTransfers = DB::table('payments')
                        ->where('sales_id', $sale->id)
                        ->where('payment_method', 'Bank Transfer');

                    if ($bankTransfers->count() !== 1) {
                        continue;
                    }

                    $bankTransfers->update([
                        'is_bftn' => true,
                        'settlement_status' => ($legacyBftn?->received_status ?? false) ? 'received' : 'pending',
                        'bftn_issue_datetime' => $legacyBftn?->bftn_date_time,
                        'received_at' => $legacyBftn?->received_at,
                    ]);

                    if (! $legacyBftn) {
                        $timestamp = $sale->created_at ?? now();
                        DB::table('bftn')->insert([
                            'sales_id' => $sale->id,
                            'bftn_date_time' => null,
                            'status' => 0,
                            'notifications_status' => 1,
                            'received_status' => false,
                            'received_at' => null,
                            'created_at' => $timestamp,
                            'updated_at' => $sale->updated_at ?? $timestamp,
                        ]);
                    }
                }
            });

        DB::table('ship_ticket_sales')
            ->select(['id', 'received_amount', 'due_amount', 'bftn_status', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($sales): void {
                foreach ($sales as $sale) {
                    $payments = DB::table('payments')->where('sales_id', $sale->id)->get();
                    $hadPaymentRows = $payments->isNotEmpty();
                    $paymentTotal = (float) $payments->sum('received_amount');
                    $missingAmount = round(max((float) $sale->received_amount - $paymentTotal, 0), 2);

                    if ($missingAmount > 0) {
                        $legacyBftn = $sale->bftn_status === 'yes'
                            ? DB::table('bftn')->where('sales_id', $sale->id)->first()
                            : null;
                        $isLegacyBftnOnly = $sale->bftn_status === 'yes' && ! $hadPaymentRows;
                        $timestamp = $sale->created_at ?? now();

                        DB::table('payments')->insert([
                            'sales_id' => $sale->id,
                            'payment_method' => $isLegacyBftnOnly ? 'Bank Transfer' : 'Not specified',
                            'is_bftn' => $isLegacyBftnOnly,
                            'settlement_status' => $isLegacyBftnOnly && ! ($legacyBftn?->received_status ?? false)
                                ? 'pending'
                                : 'received',
                            'received_amount' => $missingAmount,
                            'paid_date' => $timestamp,
                            'payment_datetime' => $timestamp,
                            'bftn_issue_datetime' => $isLegacyBftnOnly ? $legacyBftn?->bftn_date_time : null,
                            'received_at' => $isLegacyBftnOnly ? $legacyBftn?->received_at : null,
                            'created_at' => $timestamp,
                            'updated_at' => $sale->updated_at ?? $timestamp,
                        ]);

                        $payments = DB::table('payments')->where('sales_id', $sale->id)->get();
                    }

                    $settledAmount = (float) $payments
                        ->filter(fn (object $payment): bool => ! $payment->is_bftn || $payment->settlement_status === 'received')
                        ->sum('received_amount');
                    $dueAdjustment = (float) $sale->received_amount - $settledAmount;

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update([
                            'received_amount' => $settledAmount,
                            'due_amount' => max((float) $sale->due_amount + $dueAdjustment, 0),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['is_bftn', 'settlement_status', 'bftn_issue_datetime', 'received_at']);
        });
    }
};
