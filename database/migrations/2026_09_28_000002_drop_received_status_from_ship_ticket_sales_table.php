<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->dropColumn('received_status');
        });
    }

    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->boolean('received_status')->default(false)->after('bftn_status');
        });
    }
};
