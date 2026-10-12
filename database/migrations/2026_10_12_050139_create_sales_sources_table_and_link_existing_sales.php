<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->foreignId('sales_source_id')
                ->nullable()
                ->after('sales_source')
                ->constrained('sales_sources')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sales_source_id');
        });

        Schema::dropIfExists('sales_sources');
    }
};
