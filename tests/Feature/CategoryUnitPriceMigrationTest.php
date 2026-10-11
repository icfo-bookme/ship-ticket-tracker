<?php

use App\Models\Category;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('preserves an existing category unit price when retrying the ticket value migration', function () {
    $ship = Ship::query()->create(['name' => 'Migration test ship']);
    $package = ShipPackage::query()->create([
        'ship_id' => $ship->id,
        'name' => 'Migration test package',
        'price' => 100,
    ]);
    $sale = ShipTicketSale::query()->create([
        'customer_name' => 'Migration test customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'issued_date' => now()->toDateString(),
        'status' => 'ticket-issued',
        'total_payable' => 100,
    ]);
    $category = Category::query()->create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'departure',
        'unit_price' => 123.45,
    ]);

    Schema::table('ship_ticket_sales', function ($table): void {
        $table->decimal('ticket_fee', 10, 2)->default(0);
    });

    $migration = require database_path('migrations/2026_10_08_093103_move_sale_ticket_value_to_category_unit_prices.php');
    $migration->up();

    expect((float) $category->fresh()->unit_price)->toBe(123.45)
        ->and(Schema::hasColumn('categories', 'unit_price'))->toBeTrue()
        ->and(Schema::hasColumn('ship_ticket_sales', 'ticket_fee'))->toBeFalse();
});
