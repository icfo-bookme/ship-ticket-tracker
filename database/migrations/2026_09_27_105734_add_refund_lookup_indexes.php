<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->index(['sales_id', 'status'], 'refunds_sales_status_index');
            $table->index(['sales_id', 'customer_refunded_at'], 'refunds_sales_customer_refunded_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropIndex('refunds_sales_status_index');
            $table->dropIndex('refunds_sales_customer_refunded_index');
        });
    }
};
