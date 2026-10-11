<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 2)->default(0);
        });

        DB::table('categories')
            ->orderBy('id')
            ->chunkById(500, function ($categories): void {
                foreach ($categories as $category) {
                    $package = DB::table('ship_packages')->where('id', $category->package_id)->first();
                    $sale = DB::table('ship_ticket_sales')->where('id', $category->ticket_id)->first();
                    if (! $package || ! $sale) {
                        throw new \RuntimeException("Category {$category->id} does not reference an existing package and sale.");
                    }

                    $unitPrice = (float) $package->price;

                    if ($category->type === 'return' && ! empty($sale->return_date) && (float) $package->round_trip_price > 0) {
                        $unitPrice = (float) $package->round_trip_price - (float) $package->price;
                    }

                    DB::table('categories')
                        ->where('id', $category->id)
                        ->update(['unit_price' => round(max($unitPrice, 0), 2)]);
                }
            });

        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->dropColumn('ticket_fee');
        });
    }

    public function down(): void
    {
        Schema::table('ship_ticket_sales', function (Blueprint $table): void {
            $table->decimal('ticket_fee', 10, 2)->default(0);
        });

        DB::table('ship_ticket_sales')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($sales): void {
                foreach ($sales as $sale) {
                    $ticketValue = DB::table('categories')
                        ->where('ticket_id', $sale->id)
                        ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total')
                        ->value('total');

                    DB::table('ship_ticket_sales')
                        ->where('id', $sale->id)
                        ->update(['ticket_fee' => $ticketValue]);
                }
            });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('unit_price');
        });
    }
};
