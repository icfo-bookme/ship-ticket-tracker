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
        DB::table('bftn')
            ->whereNull('payment_id')
            ->orderBy('id')
            ->chunkById(500, function ($records): void {
                foreach ($records as $record) {
                    $payments = DB::table('payments')
                        ->where('sales_id', $record->sales_id)
                        ->where('is_bftn', true)
                        ->pluck('id');

                    if ($payments->count() === 1) {
                        DB::table('bftn')
                            ->where('id', $record->id)
                            ->update(['payment_id' => $payments->first()]);
                    }
                }
            });

        $hasUnmappedRecords = DB::table('bftn')->whereNull('payment_id')->exists();

        $duplicatePayments = DB::table('bftn')
            ->select('payment_id')
            ->groupBy('payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('payment_id');

        if ($hasUnmappedRecords || $duplicatePayments->isNotEmpty()) {
            return;
        }

        Schema::table('bftn', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
        });

        Schema::table('bftn', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_id')->nullable(false)->change();
            $table->unique('payment_id');
            $table->dropIndex('bftn_sales_id_index');
            $table->dropColumn('sales_id');
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->unsignedBigInteger('sales_id')->nullable()->after('id');
            $table->unsignedBigInteger('payment_id')->nullable()->change();
            $table->dropUnique(['payment_id']);
            $table->index('sales_id');
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });

        DB::table('bftn')
            ->join('payments', 'payments.id', '=', 'bftn.payment_id')
            ->update(['bftn.sales_id' => DB::raw('payments.sales_id')]);

        Schema::table('bftn', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_id')->nullable(false)->change();
        });
    }
};
