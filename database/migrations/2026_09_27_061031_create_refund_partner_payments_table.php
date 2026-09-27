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
        Schema::create('refund_partner_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id');
            $table->decimal('requested_amount', 12, 2)->default(0);
            $table->decimal('received_amount', 12, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 150)->nullable();
            $table->string('payment_proof')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->index('refund_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_partner_payments');
    }
};
