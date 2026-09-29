<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->dateTime('received_at')->nullable()->after('received_status');
        });
    }

    public function down(): void
    {
        Schema::table('bftn', function (Blueprint $table): void {
            $table->dropColumn('received_at');
        });
    }
};
