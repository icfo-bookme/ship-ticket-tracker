<?php

use App\Models\Company;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Models\RefundTicket;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Reports\RefundReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('serves the separate refund report page and data endpoint to report viewers', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));

    $response = $this->actingAs($user)
        ->get(route('refund-reports.index'))
        ->assertOk()
        ->assertSee('Refund Report')
        ->assertSee('refundReportTable')
        ->assertSee('refundReportTable-page-loader')
        ->assertSee('id="mobile-sidebar-toggle"', false)
        ->assertSee('id="sidebar-backdrop"', false)
        ->assertSee('id="divHide"', false)
        ->assertSee('min-h-12 max-h-12', false)
        ->assertSee('shrink-0', false)
        ->assertDontSee('refundReportStartDate')
        ->assertDontSee('refundReportEndDate')
        ->assertDontSee('Requested from');

    expect(strpos($response->getContent(), 'id="refundReportTable"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="refundReportCount"'));

    $this->actingAs($user)
        ->getJson(route('refund-reports.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 0)
        ->assertJsonPath('totals.request_count', 0);
});

it('reports refund amounts, due adjustment preview, ticket details, and customer payments', function () {
    $ship = Ship::create(['name' => 'Storm Route']);
    $company = Company::create(['name' => 'Ferry Company']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Refund Customer',
        'customer_mobile' => '01700000000',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'ticket_fee' => 100,
        'other_fee' => 5,
        'discount_amount' => 5,
        'total_payable' => 100,
        'received_amount' => 60,
        'due_amount' => 40,
        'issued_date' => now()->toDateString(),
        'journey_date' => now()->addDay()->toDateString(),
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ]);
    $refund = Refund::create([
        'sales_id' => $sale->id,
        'status' => 'requested',
        'refund_type' => 'bulk',
        'refunded_number_of_tickets' => 2,
        'refunded_amount' => 100,
        'gross_refund_amount' => 100,
        'refund_discount_amount' => 5,
        'other_fee_deduction' => 5,
        'customer_charge_percent' => 0,
        'customer_charge_amount' => 0,
        'partner_share_percent' => 0,
        'partner_share_amount' => 0,
        'customer_refund_amount' => 90,
        'requested_at' => now(),
    ]);
    RefundTicket::create([
        'refund_id' => $refund->id,
        'category_name' => 'Deck seat',
        'category_type' => 'departure',
        'purchased_quantity' => 2,
        'refunded_quantity' => 2,
        'unit_amount' => 50,
        'gross_amount' => 100,
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $refund->id,
        'amount' => 50,
        'payment_method' => 'Cash',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $result = app(RefundReportService::class)->dataTable(new Request([
        'draw' => 3,
        'start' => 0,
        'length' => 10,
        'status' => 'requested',
        'refund_type' => 'bulk',
        'ship_id' => $ship->id,
    ]));

    expect($result['draw'])->toBe(3)
        ->and($result['recordsTotal'])->toBe(1)
        ->and($result['recordsFiltered'])->toBe(1)
        ->and($result['data'][0]['customer_name'])->toBe('Refund Customer')
        ->and($result['data'][0]['due_adjusted_amount'])->toBe('40.00')
        ->and($result['data'][0]['final_customer_refund'])->toBe('50.00')
        ->and($result['data'][0]['customer_refund_paid'])->toBe('50.00')
        ->and($result['data'][0]['tickets'][0]['category_name'])->toBe('Deck seat')
        ->and($result['totals']['open_count'])->toBe(1)
        ->and($result['totals']['gross_amount'])->toBe('100.00')
        ->and($result['totals']['customer_refund_paid'])->toBe('50.00');
});

it('filters refund report rows by status and requested date', function () {
    $ship = Ship::create(['name' => 'Report Ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Completed Refund Customer',
        'customer_mobile' => '01700000001',
        'ship_id' => $ship->id,
        'ticket_fee' => 20,
        'total_payable' => 20,
        'received_amount' => 20,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 1,
        'status' => 'refunded',
    ]);
    Refund::create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refund_type' => 'partial',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 20,
        'gross_refund_amount' => 20,
        'customer_refund_amount' => 20,
        'requested_at' => now()->subDays(4),
        'customer_refunded_at' => now()->subDays(3),
    ]);

    $result = app(RefundReportService::class)->dataTable(new Request([
        'status' => 'completed',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->subDays(2)->toDateString(),
    ]));

    expect($result['recordsFiltered'])->toBe(1)
        ->and($result['totals']['completed_count'])->toBe(1)
        ->and($result['data'][0]['status'])->toBe('completed');
});

it('reports extra payment refunds paid separately and excludes unpaid payments', function () {
    $ship = Ship::create(['name' => 'Extra Refund Ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Extra Refund Customer',
        'customer_mobile' => '01700000002',
        'ship_id' => $ship->id,
        'ticket_fee' => 100,
        'total_payable' => 100,
        'received_amount' => 120,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
    ]);
    $extraRefund = Refund::create([
        'sales_id' => $sale->id,
        'status' => 'payment_details_added',
        'refund_type' => 'extra_payment',
        'refunded_number_of_tickets' => 0,
        'refunded_amount' => 20,
        'gross_refund_amount' => 20,
        'customer_refund_amount' => 20,
        'requested_at' => now(),
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $extraRefund->id,
        'amount' => 12,
        'payment_method' => 'Cash',
        'status' => 'paid',
        'paid_at' => now(),
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $extraRefund->id,
        'amount' => 8,
        'payment_method' => 'Bkash',
        'status' => 'pending',
    ]);

    $result = app(RefundReportService::class)->dataTable(new Request([]));

    expect($result['totals']['extra_payment_refund_paid'])->toBe('12.00')
        ->and($result['data'][0]['customer_refund_paid'])->toBe('12.00');
});

it('counts a completed extra refund without payment rows as paid, matching extra payment accounting', function () {
    $ship = Ship::create(['name' => 'Settled Refund Ship']);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Settled Extra Refund Customer',
        'customer_mobile' => '01700000003',
        'ship_id' => $ship->id,
        'ticket_fee' => 50,
        'total_payable' => 50,
        'received_amount' => 60,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
    ]);
    Refund::create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refund_type' => 'extra_payment',
        'refunded_number_of_tickets' => 0,
        'refunded_amount' => 10,
        'gross_refund_amount' => 10,
        'customer_refund_amount' => 10,
        'requested_at' => now(),
        'customer_refunded_at' => now(),
    ]);

    $result = app(RefundReportService::class)->dataTable(new Request([]));

    expect($result['totals']['extra_payment_refund_paid'])->toBe('10.00')
        ->and($result['data'][0]['customer_refund_paid'])->toBe('10.00');
});
