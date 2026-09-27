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
        Schema::create('refund_request_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('category_name', 150)->nullable();
            $table->string('category_type', 100)->nullable();
            $table->unsignedInteger('purchased_quantity')->default(0);
            $table->unsignedInteger('refunded_quantity')->default(0);
            $table->decimal('unit_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->text('remark')->nullable();
            $table->index(['refund_id', 'category_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_request_tickets');
    }
};
