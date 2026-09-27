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
            $table->boolean('collect_from_office')->default(false)->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table) {
            $table->dropColumn('collect_from_office');
        });
    }
};
