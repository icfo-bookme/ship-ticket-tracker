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
        Schema::create('ship_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ship_id');
            $table->string('name', 250);
            $table->timestamps();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('round_trip_price', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_packages');
    }
};
