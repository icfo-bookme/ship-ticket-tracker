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
        Schema::create('bftn', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_id');
            $table->dateTime('bftn_date_time')->nullable();
            $table->integer('status')->default(0);
            $table->integer('notifications_status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bftn');
    }
};
