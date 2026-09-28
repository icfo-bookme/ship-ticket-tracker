<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->boolean('received_status')->default(false)->after('status');
        });

        DB::table('ship_ticket_sales')
            ->where('bftn_status', 'yes')
            ->where('received_status', true)
            ->orderBy('id')
            ->eachById(function (object $sale): void {
                DB::table('bftn')
                    ->where('sales_id', $sale->id)
                    ->update(['received_status' => true]);
            });
    }

    public function down(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->dropColumn('received_status');
        });
    }
};
