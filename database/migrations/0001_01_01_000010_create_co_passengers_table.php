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
        Schema::create('co_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ship_ticket_sale_id')->index();
            $table->string('name');
            $table->string('nid')->nullable();
            $table->string('co_passernger_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();

            $table->foreign('ship_ticket_sale_id')
                ->references('id')
                ->on('ship_ticket_sales')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_passengers');
    }
};
