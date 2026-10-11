<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ship_ticket_sales', 'number_of_ticket')) {
            Schema::table('ship_ticket_sales', function (Blueprint $table): void {
                $table->dropColumn('number_of_ticket');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ship_ticket_sales', 'number_of_ticket')) {
            Schema::table('ship_ticket_sales', function (Blueprint $table): void {
                $table->integer('number_of_ticket')->nullable();
            });
        }
    }
};
