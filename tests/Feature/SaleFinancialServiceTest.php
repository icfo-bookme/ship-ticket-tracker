<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Services\Sales\SaleFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    $ticketValue = app(SaleFinancialService::class)->ticketValueFromPackageQuantities(
        $ship->id,
        '2026-10-20',
        [$package->id => 2],
        [$package->id => 1],
    );

    expect($ticketValue)->toBe(270.0);
});

it('calculates each departure and return category using its own persisted unit price', function () {
    $ship = Ship::create(['name' => 'Mixed Category Ship', 'status' => 1]);
    $departure = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Departure category',
        'price' => 200,
        'round_trip_price' => 390,
    ]);
    $return = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Return category',
        'price' => 100,
        'round_trip_price' => 180,
    ]);

    $ticketValue = app(SaleFinancialService::class)->ticketValueFromPackageQuantities(
        $ship->id,
        '2026-10-20',
        [$departure->id => 3],
        [$return->id => 2],
    );

    expect($ticketValue)->toBe(760.0);
});

it('counts all return quantities independently from departure quantities', function () {
    $ship = Ship::create(['name' => 'Independent Return Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Round trip package',
        'price' => 200,
        'round_trip_price' => 390,
    ]);

    $ticketValue = app(SaleFinancialService::class)->ticketValueFromPackageQuantities(
        $ship->id,
        '2026-10-20',
        [$package->id => 2],
        [$package->id => 3],
    );

    expect($ticketValue)->toBe(970.0);
});

it('ignores zero-quantity package ids that are not available for the selected ship', function () {
    $ship = Ship::create(['name' => 'Zero Quantity Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Current package',
        'price' => 100,
        'round_trip_price' => 170,
    ]);

    $ticketValue = app(SaleFinancialService::class)->ticketValueFromPackageQuantities(
        $ship->id,
        '2026-10-20',
        [$package->id => 2, 999999 => 0],
        [999999 => 0],
    );

    expect($ticketValue)->toBe(200.0);
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

    $ticketValue = app(SaleFinancialService::class)->ticketValueFromCategories(
        $ship->id,
        '2026-10-20',
        [
            'departure' => [['package_id' => $departure->id, 'quantity' => 1]],
            'return' => [['package_id' => $return->id, 'quantity' => 1]],
        ],
        true,
    );

    expect($ticketValue)->toBe(190.0);
});

it('derives payable and due values and refuses a package belonging to another ship', function () {
    $summary = app(SaleFinancialService::class)->summary(300, 20, 10, 100);

    expect($summary)->toBe([
        'ticket_value' => 300.0,
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

    expect(fn () => app(SaleFinancialService::class)->ticketValueFromCategories(
        $ship->id,
        null,
        ['departure' => [['package_id' => $package->id, 'quantity' => 1]]],
    ))->toThrow(ValidationException::class);
});

it('excludes pending BFTN from due while keeping it out of received', function () {
    $summary = app(SaleFinancialService::class)->summary(100, 0, 0, 0, 100);

    expect($summary['received_amount'])->toBe(0.0)
        ->and($summary['due_amount'])->toBe(0.0);

    $ship = Ship::create(['name' => 'BFTN Summary Ship', 'status' => 1]);
    $company = Company::create(['name' => 'BFTN Summary Company', 'status' => 1]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Pending BFTN Customer',
        'customer_mobile' => '01700000000',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'ticket_value' => 100,
        'total_payable' => 100,
        'received_amount' => 0,
        'due_amount' => 100,
        'issued_date' => now()->toDateString(),
        'journey_date' => now()->addDay()->toDateString(),
        'ticket_count' => 1,
        'status' => 'pending',
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 100,
    ]);

    $currentSummary = app(SaleFinancialService::class)->currentSummary($sale);

    expect($currentSummary['received_amount'])->toBe(0.0)
        ->and($currentSummary['due_amount'])->toBe(0.0);
});

it('derives a sale ticket count from its category quantities', function () {
    $ship = Ship::create(['name' => 'Category Count Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Category Count Package',
        'price' => 100,
        'round_trip_price' => 170,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Category Count Customer',
        'customer_mobile' => '01700000001',
        'ship_id' => $ship->id,
        'return_date' => now()->addDays(2)->toDateString(),
        'ticket_value' => 300,
        'total_payable' => 300,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'status' => 'ticket-issued',
    ]);

    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 2,
        'type' => 'departure',
        'unit_price' => 100,
    ]);
    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'return',
        'unit_price' => 70,
    ]);

    expect($sale->fresh()->ticket_count)->toBe(3)
        ->and(Schema::hasColumn('ship_ticket_sales', 'number_of_ticket'))->toBeFalse()
        ->and(Schema::hasColumn('ship_ticket_sales', 'ticket_fee'))->toBeFalse()
        ->and(Schema::hasColumn('categories', 'unit_price'))->toBeTrue()
        ->and((float) $sale->fresh()->ticket_value)->toBe(270.0);

    $package->update(['price' => 200, 'round_trip_price' => 350]);

    expect((float) $sale->fresh()->ticket_value)->toBe(270.0);
});

it('calculates current due from categories, payments, pending BFTN and completed refund adjustments', function () {
    $ship = Ship::create(['name' => 'Live Due Ship', 'status' => 1]);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Live Due Package',
        'price' => 100,
        'round_trip_price' => 170,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Live Due Customer',
        'customer_mobile' => '01700000002',
        'ship_id' => $ship->id,
        'other_fee' => 20,
        'discount_amount' => 10,
        'total_payable' => 310,
        'issued_date' => now()->toDateString(),
        'journey_date' => now()->addDay()->toDateString(),
        'status' => 'pending',
    ]);

    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 3,
        'type' => 'departure',
        'unit_price' => 100,
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 100,
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 50,
    ]);
    Refund::create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refund_type' => 'partial',
        'refunded_number_of_tickets' => 0,
        'refunded_amount' => 0,
        'due_adjusted_amount' => 20,
    ]);

    $freshSale = $sale->fresh();

    expect(Schema::hasColumn('ship_ticket_sales', 'due_amount'))->toBeFalse()
        ->and((float) $freshSale->due_amount)->toBe(140.0)
        ->and((float) app(SaleFinancialService::class)->currentSummary($freshSale)['due_amount'])->toBe(140.0)
        ->and(ShipTicketSale::query()->withDue()->whereKey($sale->id)->exists())->toBeTrue();
});
