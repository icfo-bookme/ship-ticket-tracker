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
        Schema::create('sale_draft_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ship_package_id')->nullable()->constrained('ship_packages')->nullOnDelete();
            $table->string('category_name', 250);
            $table->string('journey_type', 20);
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->index(['ship_package_id', 'journey_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_draft_categories');
    }
};
