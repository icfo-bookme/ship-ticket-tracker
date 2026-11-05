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
        Schema::table('verify_tracker', function (Blueprint $table) {
            $table->index(['ticket_id', 'name', 'created_at'], 'verify_tracker_ticket_issue_history_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verify_tracker', function (Blueprint $table) {
            $table->dropIndex('verify_tracker_ticket_issue_history_index');
        });
    }
};
