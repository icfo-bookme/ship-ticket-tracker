<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_error_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('refund_id')->unique()->constrained('refunds')->restrictOnDelete();
            $table->string('loss_type');
            $table->decimal('loss_percent', 5, 2);
            $table->decimal('loss_amount', 12, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_error_refunds');
    }
};
