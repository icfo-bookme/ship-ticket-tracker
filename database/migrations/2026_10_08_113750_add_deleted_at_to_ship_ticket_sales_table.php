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
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->softDeletes();
            $table->index('deleted_at', 'ship_ticket_sales_deleted_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->dropIndex('ship_ticket_sales_deleted_at_index');
            $table->dropSoftDeletes();
        });
    }
};
