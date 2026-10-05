<?php

use App\Models\Ship;
use App\Models\ShipPackage;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('calculates staff ticket fees from selected ship package prices', function () {
    $ship = Ship::create(['name' => 'Financial Test Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Standard',
        'price' => 100,
        'round_trip_price' => 170,
    ]);

    $ticketFee = app(SaleFinancialService::class)->ticketFeeFromPackageQuantities(
        $ship->id,
        '2026-10-20',
        [$package->id => 2],
        [$package->id => 1],
        9999,
    );

    expect($ticketFee)->toBe(270.0);
});

it('calculates public cross-package round trips using the return fare', function () {
    $ship = Ship::create(['name' => 'Public Test Ship', 'status' => 1]);
    $departure = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Departure package',
        'price' => 100,
        'round_trip_price' => 170,
    ]);
    $return = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Return package',
        'price' => 150,
        'round_trip_price' => 240,
    ]);

    $ticketFee = app(SaleFinancialService::class)->ticketFeeFromCategories(
        $ship->id,
        '2026-10-20',
        [
            'departure' => [['package_id' => $departure->id, 'quantity' => 1]],
            'return' => [['package_id' => $return->id, 'quantity' => 1]],
        ],
        9999,
        true,
    );

    expect($ticketFee)->toBe(190.0);
});

it('derives payable and due values and refuses a package belonging to another ship', function () {
    $summary = app(SaleFinancialService::class)->summary(300, 20, 10, 100);

    expect($summary)->toBe([
        'ticket_fee' => 300.0,
        'total_payable' => 310.0,
        'received_amount' => 100.0,
        'due_amount' => 210.0,
    ]);

    expect(fn () => app(SaleFinancialService::class)->summary(100, 0, 101, 0))
        ->toThrow(ValidationException::class);

    $ship = Ship::create(['name' => 'Owner Ship', 'status' => 1]);
    $otherShip = Ship::create(['name' => 'Other Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $otherShip->id,
        'name' => 'Not on selected ship',
        'price' => 50,
        'round_trip_price' => 80,
    ]);

    expect(fn () => app(SaleFinancialService::class)->ticketFeeFromCategories(
        $ship->id,
        null,
        ['departure' => [['package_id' => $package->id, 'quantity' => 1]]],
        0,
    ))->toThrow(ValidationException::class);
});
