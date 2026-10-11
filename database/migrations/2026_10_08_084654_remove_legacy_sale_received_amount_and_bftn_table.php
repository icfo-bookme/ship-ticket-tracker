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
        if (Schema::hasColumn('ship_ticket_sales', 'received_amount')) {
            DB::table('ship_ticket_sales')
                ->select(['id', 'received_amount', 'created_at', 'updated_at'])
                ->where('received_amount', '>', 0)
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('payments')
                        ->whereColumn('payments.sales_id', 'ship_ticket_sales.id');
                })
                ->orderBy('id')
                ->chunkById(500, function ($sales): void {
                    foreach ($sales as $sale) {
                        $timestamp = $sale->created_at ?? now();
                        DB::table('payments')->insert([
                            'sales_id' => $sale->id,
                            'payment_method' => 'Not specified',
                            'is_bftn' => false,
                            'settlement_status' => 'received',
                            'received_amount' => $sale->received_amount,
                            'paid_date' => $timestamp,
                            'payment_datetime' => $timestamp,
                            'created_at' => $timestamp,
                            'updated_at' => $sale->updated_at ?? $timestamp,
                        ]);
                    }
                });
        }

        if (! Schema::hasColumn('payments', 'bftn_notification_read_at')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dateTime('bftn_notification_read_at')->nullable();
            });
        }

        if (Schema::hasTable('bftn') && Schema::hasColumn('bftn', 'notifications_status')) {
            $hasBftnPaymentId = Schema::hasColumn('bftn', 'payment_id');
            $hasBftnSaleId = Schema::hasColumn('bftn', 'sales_id');

            DB::table('bftn')
                ->where('notifications_status', 0)
                ->orderBy('id')
                ->chunkById(500, function ($notifications) use ($hasBftnPaymentId, $hasBftnSaleId): void {
                    foreach ($notifications as $notification) {
                        $readAt = $notification->updated_at ?? now();

                        if ($hasBftnPaymentId && $notification->payment_id !== null) {
                            DB::table('payments')
                                ->where('id', $notification->payment_id)
                                ->update(['bftn_notification_read_at' => $readAt]);

                            continue;
                        }

                        if ($hasBftnSaleId) {
                            DB::table('payments')
                                ->where('sales_id', $notification->sales_id)
                                ->where('is_bftn', true)
                                ->update(['bftn_notification_read_at' => $readAt]);
                        }
                    }
                });
        }

        Schema::dropIfExists('bftn');

        $legacySaleColumns = array_values(array_filter(
            ['received_amount', 'bftn_status', 'number_of_ticket'],
            fn (string $column): bool => Schema::hasColumn('ship_ticket_sales', $column),
        ));

        if ($legacySaleColumns !== []) {
            Schema::table('ship_ticket_sales', function (Blueprint $table) use ($legacySaleColumns): void {
                $table->dropColumn($legacySaleColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToRestore = array_values(array_filter(
            ['received_amount', 'bftn_status', 'number_of_ticket'],
            fn (string $column): bool => ! Schema::hasColumn('ship_ticket_sales', $column),
        ));

        if ($columnsToRestore !== []) {
            Schema::table('ship_ticket_sales', function (Blueprint $table) use ($columnsToRestore): void {
                foreach ($columnsToRestore as $column) {
                    if ($column === 'received_amount') {
                        $table->decimal('received_amount', 10, 2)->default(0);
                    } elseif ($column === 'bftn_status') {
                        $table->string('bftn_status', 50)->nullable();
                    } else {
                        $table->integer('number_of_ticket')->nullable();
                    }
                }
            });
        }

        DB::table('ship_ticket_sales')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($sales): void {
                foreach ($sales as $sale) {
                    $receivedAmount = DB::table('payments')
                        ->where('sales_id', $sale->id)
                        ->where(fn ($query) => $query->where('is_bftn', false)->orWhere('settlement_status', 'received'))
                        ->sum('received_amount');

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update(['received_amount' => $receivedAmount]);
                }
            });

        DB::table('ship_ticket_sales')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.sales_id', 'ship_ticket_sales.id')
                    ->where('payments.is_bftn', true);
            })
            ->update(['bftn_status' => 'yes']);

        DB::table('ship_ticket_sales')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($sales): void {
                foreach ($sales as $sale) {
                    $ticketCount = DB::table('categories')
                        ->where('ticket_id', $sale->id)
                        ->sum('quantity');

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update(['number_of_ticket' => $ticketCount]);
                }
            });

        if (! Schema::hasTable('bftn')) {
            Schema::create('bftn', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('payment_id')->unique();
                $table->dateTime('bftn_date_time')->nullable();
                $table->integer('status')->default(0);
                $table->integer('notifications_status')->default(1);
                $table->boolean('received_status')->default(false);
                $table->dateTime('received_at')->nullable();
                $table->timestamps();
                $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            });
        }

        DB::table('payments')
            ->where('is_bftn', true)
            ->orderBy('id')
            ->chunkById(500, function ($payments): void {
                foreach ($payments as $payment) {
                    DB::table('bftn')->insert([
                        'payment_id' => $payment->id,
                        'bftn_date_time' => $payment->bftn_issue_datetime,
                        'status' => 0,
                        'notifications_status' => $payment->bftn_notification_read_at ? 0 : 1,
                        'received_status' => $payment->settlement_status === 'received',
                        'received_at' => $payment->received_at,
                        'created_at' => $payment->created_at ?? now(),
                        'updated_at' => $payment->updated_at ?? now(),
                    ]);
                }
            });

        if (Schema::hasColumn('payments', 'bftn_notification_read_at')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropColumn('bftn_notification_read_at');
            });
        }
    }
};
