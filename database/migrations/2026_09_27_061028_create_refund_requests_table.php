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
        Schema::table('refunds', function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->index()->after('sales_id');
            $table->string('refund_type', 30)->default('partial')->after('batch_uuid');
            $table->string('reason', 100)->nullable()->after('refund_type');
            $table->decimal('gross_refund_amount', 12, 2)->default(0)->after('refunded_amount');
            $table->decimal('customer_charge_percent', 5, 2)->default(0)->after('gross_refund_amount');
            $table->decimal('customer_charge_amount', 12, 2)->default(0)->after('customer_charge_percent');
            $table->decimal('partner_share_percent', 5, 2)->default(0)->after('customer_charge_amount');
            $table->decimal('partner_share_amount', 12, 2)->default(0)->after('partner_share_percent');
            $table->decimal('customer_refund_amount', 12, 2)->default(0)->after('partner_share_amount');
            $table->decimal('company_retained_amount', 12, 2)->default(0)->after('customer_refund_amount');
            $table->timestamp('requested_at')->nullable()->after('remark');
            $table->timestamp('partner_received_at')->nullable()->after('requested_at');
            $table->timestamp('customer_refunded_at')->nullable()->after('partner_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn([
                'batch_uuid',
                'refund_type',
                'reason',
                'gross_refund_amount',
                'customer_charge_percent',
                'customer_charge_amount',
                'partner_share_percent',
                'partner_share_amount',
                'customer_refund_amount',
                'company_retained_amount',
                'requested_at',
                'partner_received_at',
                'customer_refunded_at',
            ]);
        });
    }
};
