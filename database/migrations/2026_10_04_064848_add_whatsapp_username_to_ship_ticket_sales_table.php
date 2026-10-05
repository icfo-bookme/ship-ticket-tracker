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
        Schema::table('ship_ticket_sales', function (Blueprint $table) {
            $table->string('whatsapp_username', 100)->nullable()->after('whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table) {
            $table->dropColumn('whatsapp_username');
        });
    }
};
