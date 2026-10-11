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
        Schema::table('bftn', function (Blueprint $table): void {
            $table->foreignId('payment_id')
                ->nullable()
                ->after('id')
                ->constrained('payments')
                ->nullOnDelete();
        });

        DB::table('bftn')
            ->join('payments', function ($join): void {
                $join->on('payments.sales_id', '=', 'bftn.sales_id')
                    ->where('payments.is_bftn', true);
            })
            ->whereRaw('(SELECT COUNT(*) FROM payments AS bftn_payments WHERE bftn_payments.sales_id = bftn.sales_id AND bftn_payments.is_bftn = 1) = 1')
            ->select(['bftn.id', 'payments.id as payment_id'])
            ->orderBy('bftn.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('bftn')
                    ->where('id', $row->id)
                    ->update(['payment_id' => $row->payment_id]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_id');
        });
    }
};
