<?php

use App\Models\Category;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Services\Refunds\RefundListingService;
use App\Services\Refunds\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('returns bounded empty datatable payloads for refund listings', function () {
    $service = app(RefundListingService::class);
    $request = Request::create('/refunds?draw=7&start=-5&length=10000', 'GET');

    $refundable = $service->refundableSales($request);
    $refunded = $service->refundedSales($request);
    $requests = $service->refundRequests($request);

    expect($refundable['draw'])->toBe('7')
        ->and($refundable['recordsTotal'])->toBe(0)
        ->and($refundable['data'])->toHaveCount(0)
        ->and($refunded['recordsFiltered'])->toBe(0)
        ->and($refunded['data'])->toHaveCount(0)
        ->and($requests['recordsFiltered'])->toBe(0)
        ->and($requests['data'])->toHaveCount(0);
});

it('keeps due adjustment nonnegative for legacy negative refund amounts', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 100,
        'received_amount' => 80,
        'due_amount' => 20,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    Refund::create([
        'sales_id' => $sale->id,
        'status' => 'requested',
        'refund_type' => 'partial',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 10,
        'gross_refund_amount' => 10,
        'customer_refund_amount' => -2.89,
        'requested_at' => now(),
    ]);

    $result = app(RefundListingService::class)->refundRequests(Request::create('/refund-requests?status=requested'));
    $refund = $result['data']->sole();

    expect($refund->due_adjusted_amount)->toBe('0.00')
        ->and($refund->customer_refund_after_due_adjustment)->toBe(0.0)
        ->and($refund->refund_calculation_valid)->toBeFalse();
});

it('calculates the current due from recorded payments instead of stale sale totals', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 100,
        'received_amount' => 100,
        'due_amount' => 0,
        'total_payable' => 100,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 40,
    ]);

    $result = app(RefundListingService::class)->refundableSales(Request::create('/refunds'));
    $listedSale = $result['data']->sole();

    expect($listedSale->received_amount)->toBe('40.00')
        ->and($listedSale->due_amount)->toBe('60.00');
});

it('accounts for the sale discount when listing due and overpayment amounts', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 110,
        'discount_amount' => 10,
        'other_fee' => 0,
        'total_payable' => 100,
        'received_amount' => 100,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 110,
    ]);

    $result = app(RefundListingService::class)->refundableSales(Request::create('/refunds'));
    $listedSale = $result['data']->sole();

    expect($listedSale->total_payable)->toBe(100.0)
        ->and($listedSale->received_amount)->toBe('110.00')
        ->and($listedSale->due_amount)->toBe('0.00')
        ->and($listedSale->extra_received_amount)->toBe(10.0)
        ->and($listedSale->extra_remaining_amount)->toBe(10.0);
});

it('calculates partial refund totals from selected category quantities and applies discount and due', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $departurePackage = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Package 1',
        'price' => 10,
        'round_trip_price' => 19,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 38,
        'other_fee' => 25.8,
        'discount_amount' => 3.8,
        'total_payable' => 60,
        'received_amount' => 0,
        'due_amount' => 60,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 4,
        'status' => 'ticket-issued',
    ]);
    $departure = Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $departurePackage->id,
        'quantity' => 2,
        'type' => 'departure',
    ]);
    $return = Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $departurePackage->id,
        'quantity' => 2,
        'type' => 'return',
    ]);

    app(RefundService::class)->partialRefund($sale, [
        'ticket_selections' => [
            ['category_id' => $departure->id, 'refunded_quantity' => 2],
            ['category_id' => $return->id, 'refunded_quantity' => 2],
        ],
        'customer_charge_percent' => 5,
        'partner_share_percent' => 4,
        'remark' => null,
    ]);

    $refund = Refund::query()->sole();
    $listing = app(RefundListingService::class)->refundRequests(Request::create('/refund-requests'))['data']->sole();

    expect($refund->gross_refund_amount)->toBe('38.00')
        ->and($refund->refund_discount_amount)->toBe('3.80')
        ->and($refund->other_fee_deduction)->toBe('0.00')
        ->and($refund->customer_charge_amount)->toBe('1.90')
        ->and($refund->partner_share_amount)->toBe('1.52')
        ->and($refund->company_retained_amount)->toBe('0.38')
        ->and($refund->customer_refund_amount)->toBe('32.30')
        ->and((float) $listing->sale->other_fee)->toBe(25.8)
        ->and($listing->due_adjusted_amount)->toBe('32.30')
        ->and($listing->customer_refund_after_due_adjustment)->toBe(0.0);
});

it('creates a full bulk refund with returned category details and zero charge and partner share', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Package 1',
        'price' => 10,
        'round_trip_price' => 19,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Weather Cancellation Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 38,
        'other_fee' => 4,
        'discount_amount' => 3.8,
        'total_payable' => 38.2,
        'received_amount' => 0,
        'due_amount' => 38.2,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 4,
        'status' => 'ticket-issued',
    ]);
    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 2,
        'type' => 'departure',
    ]);
    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 2,
        'type' => 'return',
    ]);

    app(RefundService::class)->fullRefund([$sale->id]);

    $refund = Refund::query()->with('tickets')->sole();
    $listing = app(RefundListingService::class)->refundRequests(
        Request::create('/refund-requests?status=requested'),
    )['data']->sole();

    expect($refund->refunded_number_of_tickets)->toBe(4)
        ->and($refund->gross_refund_amount)->toBe('38.00')
        ->and($refund->refund_discount_amount)->toBe('3.80')
        ->and($refund->other_fee_deduction)->toBe('0.00')
        ->and($refund->customer_charge_percent)->toBe('0.00')
        ->and($refund->customer_charge_amount)->toBe('0.00')
        ->and($refund->partner_share_percent)->toBe('0.00')
        ->and($refund->partner_share_amount)->toBe('0.00')
        ->and($refund->customer_refund_amount)->toBe('34.20')
        ->and($refund->tickets)->toHaveCount(2)
        ->and($refund->tickets->sum('refunded_quantity'))->toBe(4)
        ->and($listing->total_refund_tickets)->toBe(4)
        ->and($listing->due_adjusted_amount)->toBe('34.20')
        ->and($listing->customer_refund_after_due_adjustment)->toBe(0.0)
        ->and($listing->edit_categories)->toHaveCount(2);

    app(RefundService::class)->refundCustomer($refund, []);
    $refund->refresh();
    $sale->refresh();

    expect($refund->due_adjusted_amount)->toBe('34.20')
        ->and($refund->customer_refund_amount)->toBe('0.00')
        ->and($sale->due_amount)->toBe('4.00')
        ->and($refund->customerPayments)->toHaveCount(0);
});

it('rejects a partial refund quantity above the purchased category quantity', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Package 1',
        'price' => 10,
        'round_trip_price' => 19,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 20,
        'received_amount' => 0,
        'due_amount' => 20,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    $category = Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 2,
        'type' => 'departure',
    ]);

    expect(fn () => app(RefundService::class)->partialRefund($sale, [
        'ticket_selections' => [
            ['category_id' => $category->id, 'refunded_quantity' => 3],
        ],
        'customer_charge_percent' => 0,
        'partner_share_percent' => 0,
    ]))->toThrow(ValidationException::class);

    expect(Refund::query()->count())->toBe(0);
});

it('does not deduct other fee from a partial ticket refund', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Package 1',
        'price' => 10,
        'round_trip_price' => 10,
    ]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 20,
        'other_fee' => 2,
        'total_payable' => 22,
        'received_amount' => 22,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    $category = Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 2,
        'type' => 'departure',
    ]);

    app(RefundService::class)->partialRefund($sale, [
        'ticket_selections' => [
            ['category_id' => $category->id, 'refunded_quantity' => 1],
        ],
        'customer_charge_percent' => 0,
        'partner_share_percent' => 0,
        'remark' => null,
    ]);

    $refund = Refund::query()->sole();

    expect($refund->gross_refund_amount)->toBe('10.00')
        ->and($refund->other_fee_deduction)->toBe('0.00')
        ->and($refund->customer_refund_amount)->toBe('10.00');
});

it('blocks completion of a legacy refund with a negative customer refund amount', function () {
    $ship = Ship::create(['name' => 'Test ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Test Customer',
        'customer_mobile' => '01234567890',
        'ship_id' => $ship->id,
        'ticket_fee' => 100,
        'received_amount' => 80,
        'due_amount' => 20,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    $refund = Refund::create([
        'sales_id' => $sale->id,
        'status' => 'payment_details_added',
        'refund_type' => 'partial',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 10,
        'gross_refund_amount' => 10,
        'customer_refund_amount' => -2.89,
        'requested_at' => now(),
    ]);

    expect(fn () => app(RefundService::class)->refundCustomer($refund, []))
        ->toThrow(ValidationException::class);
    expect($sale->fresh()->due_amount)->toBe('20.00')
        ->and($refund->fresh()->status)->toBe('payment_details_added');
});
