<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates tagged performance sales and cleans up only their related records', function () {
    $ship = Ship::query()->create(['name' => 'Performance Ship', 'status' => 1]);
    Company::query()->create(['name' => 'Performance Company', 'status' => 1]);
    ShipPackage::query()->create([
        'ship_id' => $ship->id,
        'name' => 'Performance Package',
        'price' => 100,
        'round_trip_price' => 180,
    ]);

    $this->artisan('sales:seed-performance', ['count' => 5, '--status' => 'pending'])
        ->assertExitCode(0);

    $sales = ShipTicketSale::query()->where('sales_source', 'performance-test')->get();

    expect($sales)->toHaveCount(5)
        ->and($sales->every(fn (ShipTicketSale $sale): bool => $sale->status === 'pending'))->toBeTrue()
        ->and(Category::query()->whereIn('ticket_id', $sales->modelKeys())->count())->toBe(5)
        ->and(Payment::query()->whereIn('sales_id', $sales->modelKeys())->count())->toBeGreaterThan(0);

    $this->artisan('sales:seed-performance', ['--cleanup' => true])
        ->assertExitCode(0);

    expect(ShipTicketSale::query()->where('sales_source', 'performance-test')->count())->toBe(0)
        ->and(Category::query()->whereIn('ticket_id', $sales->modelKeys())->count())->toBe(0)
        ->and(Payment::query()->whereIn('sales_id', $sales->modelKeys())->count())->toBe(0);
});
