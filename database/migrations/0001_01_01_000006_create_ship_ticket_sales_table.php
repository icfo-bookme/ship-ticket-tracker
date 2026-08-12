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
        Schema::create('ship_ticket_sales', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('customer_mobile', 20);
            $table->string('sales_source')->nullable();
            $table->unsignedBigInteger('ship_id');
            $table->date('journey_date')->nullable();
            $table->decimal('ticket_fee', 10, 2);
            $table->string('payment_method')->nullable();
            $table->decimal('received_amount', 10, 2);
            $table->decimal('due_amount', 10, 2)->default(0.00);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('issued_date');
            $table->integer('sold_by')->nullable();
            $table->timestamps();
            $table->string('status', 100)->default('pending');
            $table->string('nid')->nullable();
            $table->string('email')->nullable();
            $table->integer('number_of_ticket')->nullable();
            $table->date('return_date')->nullable();
            $table->string('ticket_category')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->text('remark1')->nullable();
            $table->text('remark2')->nullable();
            $table->string('bftn_status', 50)->nullable();
            $table->string('hotel_status')->default('pending');
            $table->string('pdf_status', 20)->default('pending');
            $table->integer('other_fee')->default(0);
            $table->integer('total_payable')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_ticket_sales');
    }
};
