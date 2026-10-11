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
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_sales_id_index');
            $table->index(
                ['sales_id', 'is_bftn', 'settlement_status', 'received_amount'],
                'payments_due_lookup_index',
            );
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropIndex('refunds_sales_id_index');
            $table->dropIndex('refunds_sales_status_index');
            $table->dropIndex('refunds_sales_customer_refunded_index');
            $table->index(['sales_id', 'status', 'due_adjusted_amount'], 'refunds_due_status_lookup_index');
            $table->index(
                ['sales_id', 'customer_refunded_at', 'due_adjusted_amount'],
                'refunds_due_refunded_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropIndex('refunds_due_status_lookup_index');
            $table->dropIndex('refunds_due_refunded_lookup_index');
            $table->index('sales_id', 'refunds_sales_id_index');
            $table->index(['sales_id', 'status'], 'refunds_sales_status_index');
            $table->index(['sales_id', 'customer_refunded_at'], 'refunds_sales_customer_refunded_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_due_lookup_index');
            $table->index('sales_id', 'payments_sales_id_index');
        });
    }
};
