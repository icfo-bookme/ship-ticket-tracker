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
        Schema::create('verify_tracker', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('verified_by')->index();
            $table->timestamps();
            $table->unsignedBigInteger('ticket_id');

            $table->foreign('verified_by')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verify_tracker');
    }
};
