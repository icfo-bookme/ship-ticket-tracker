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
            $table->dropColumn(['payment_method', 'ticket_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->string('payment_method')->nullable();
            $table->string('ticket_category')->nullable();
        });
    }
};
