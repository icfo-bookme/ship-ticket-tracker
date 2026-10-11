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
        // ship_ticket_sales — filters used by the server-side DataTable and duplicate check
        Schema::table('ship_ticket_sales', function (Blueprint $table) {
            $table->index('status');
            $table->index('ship_id');
            $table->index('company_id');
            $table->index('journey_date');
            $table->index('customer_mobile');
            $table->index(['customer_mobile', 'journey_date']);
        });

        // Foreign-key/join columns on the remaining business tables
        Schema::table('categories', function (Blueprint $table) {
            $table->index('ticket_id');
            $table->index('package_id');
        });

        Schema::table('ship_packages', function (Blueprint $table) {
            $table->index('ship_id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->index('sales_id');
        });

        Schema::table('bftn', function (Blueprint $table) {
            $table->index('sales_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('sales_id');
        });

        Schema::table('printed_tickets', function (Blueprint $table) {
            $table->index('sales_id');
            $table->index('group_by_id');
        });

        Schema::table('print_status', function (Blueprint $table) {
            $table->index('sales_id');
        });

        Schema::table('verify_tracker', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('hotel_remarks', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('whatsapp_details', function (Blueprint $table) {
            $table->index('tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'ship_ticket_sales' => [
                'ship_ticket_sales_customer_mobile_journey_date_index',
                'ship_ticket_sales_status_index',
                'ship_ticket_sales_ship_id_index',
                'ship_ticket_sales_company_id_index',
                'ship_ticket_sales_journey_date_index',
                'ship_ticket_sales_customer_mobile_index',
            ],
            'categories' => ['categories_ticket_id_index', 'categories_package_id_index'],
            'ship_packages' => ['ship_packages_ship_id_index'],
            'shipments' => ['shipments_ticket_id_index'],
            'refunds' => ['refunds_sales_id_index'],
            'bftn' => ['bftn_sales_id_index'],
            'payments' => ['payments_sales_id_index'],
            'printed_tickets' => ['printed_tickets_sales_id_index', 'printed_tickets_group_by_id_index'],
            'print_status' => ['print_status_sales_id_index'],
            'verify_tracker' => ['verify_tracker_ticket_id_index'],
            'hotel_remarks' => ['hotel_remarks_ticket_id_index'],
            'whatsapp_details' => ['whatsapp_details_tag_index'],
        ];

        foreach ($indexes as $tableName => $indexNames) {
            foreach ($indexNames as $indexName) {
                if (! Schema::hasIndex($tableName, $indexName)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->dropIndex($indexName);
                });
            }
        }
    }
};
