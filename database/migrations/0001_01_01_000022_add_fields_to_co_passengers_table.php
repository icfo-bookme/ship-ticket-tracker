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
        Schema::table('co_passengers', function (Blueprint $table) {
            if (! Schema::hasColumn('co_passengers', 'co_passernger_number')) {
                $table->string('co_passernger_number')->nullable();
            }
            if (! Schema::hasColumn('co_passengers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('co_passengers', function (Blueprint $table) {
            $table->dropColumn(['co_passernger_number', 'date_of_birth']);
        });
    }
};
