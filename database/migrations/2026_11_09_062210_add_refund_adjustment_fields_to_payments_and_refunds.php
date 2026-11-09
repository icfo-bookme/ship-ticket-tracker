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
        Schema::table('refunds', function (Blueprint $table): void {
            $table->timestamp('adjustment_started_at')->nullable();
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->restrictOnDelete();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('refund_id');
            $table->dropConstrainedForeignId('adjusted_by');
        });
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropColumn('adjustment_started_at');
        });
    }
};
