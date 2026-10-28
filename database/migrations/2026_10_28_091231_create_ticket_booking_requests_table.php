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
        Schema::create('ticket_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('mobile', 20)->index();
            $table->unsignedInteger('ticket_quantity');
            $table->string('package_name', 150);
            $table->string('trip_type', 20);
            $table->date('journey_date');
            $table->date('return_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at', 'id'], 'booking_requests_status_created_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_booking_requests');
    }
};
