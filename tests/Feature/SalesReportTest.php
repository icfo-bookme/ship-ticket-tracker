<?php

use App\Models\Bftn;
use App\Models\CashCollection;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->ship = Ship::create(['name' => 'MV Report Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Report Company', 'status' => 1]);
});

function reportSale(Ship $ship, Company $company, array $attributes = []): ShipTicketSale
{
    return ShipTicketSale::create(array_merge([
        'customer_name' => 'Report Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 300,
        'other_fee' => 0,
        'total_payable' => 300,
        'received_amount' => 300,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 2,
        'status' => 'ticket-issued',
    ], $attributes));
}

it('returns totals for the requested created date range', function () {
    reportSale($this->ship, $this->company);
    reportSale($this->ship, $this->company, ['number_of_ticket' => 1, 'ticket_fee' => 200, 'total_payable' => 200, 'received_amount' => 200]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&start_create_date='.now()->subDays(6)->toDateString().'&end_create_date='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('totals.total_number_of_tickets', 3)
        ->assertJsonPath('totals.total_ticket_fee', '500.00')
        ->assertJsonPath('totals.total_payable', '500.00')
        ->assertJsonPath('totals.total_received_amount', '500.00')
        ->assertJsonPath('totals.net_sales_amount', '500.00');
});

it('leaves sales older than seven days out of the default window', function () {
    reportSale($this->ship, $this->company);

    $oldSale = reportSale($this->ship, $this->company);
    $oldSale->created_at = now()->subDays(10);
    $oldSale->save();

    $recentResponse = $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&start_create_date='.now()->subDays(6)->toDateString().'&end_create_date='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($recentResponse->json('totals.total_number_of_tickets'))->toBe(2);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=2&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('totals.total_number_of_tickets', 4);
});

it('shows the same global available cash as cash collection regardless of report filters', function () {
    reportSale($this->ship, $this->company, [
        'customer_name' => 'Filtered Sale',
        'received_amount' => 120,
    ]);
    reportSale($this->ship, $this->company, [
        'customer_name' => 'Outside Report Search',
        'received_amount' => 80,
    ]);
    reportSale($this->ship, $this->company, [
        'customer_name' => 'Pending Sale',
        'received_amount' => 500,
        'status' => 'pending',
    ]);
    CashCollection::create([
        'name' => 'Office cash withdrawal',
        'entry_by' => $this->admin->id,
        'cashout_amount' => 30,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=Filtered%20Sale')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_received_amount', '120.00')
        ->assertJsonPath('totals.net_cash', '170.00');
});

it('preselects the last seven days on the report page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('id="startCreateDate" value="'.now()->subDays(6)->toDateString().'"', false)
        ->assertSee('id="endCreateDate" value="'.now()->toDateString().'"', false);
});

it('renders the sales report filters and summary targets', function () {
    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('id="salesTable"', false)
        ->assertSee('id="saleReportDetailModal"', false)
        ->assertSee('data-close-modal="saleReportDetailModal"', false)
        ->assertDontSee('data-modal-hide="saleReportDetailModal"', false)
        ->assertSee('Ticket Price / Other Fee')
        ->assertSee('Received / Gross Refunded')
        ->assertSee('id="totalSellTickets"', false)
        ->assertSee('id="totalCustomerRefundPaid"', false)
        ->assertSee('id="totalExtraRefundedAmount"', false)
        ->assertSee('Customer Refund Paid')
        ->assertSee('Other Fee Deducted')
        ->assertSee('id="toggleAdvancedFilters"', false)
        ->assertSee('id="advancedReportFilters"', false)
        ->assertSee('More filters')
        ->assertSee('id="payment_method"', false)
        ->assertSee('id="bftnFilter"', false);
});

it('includes every payment record in the sale report details payload', function () {
    $sale = reportSale($this->ship, $this->company, [
        'ticket_fee' => 100,
        'total_payable' => 100,
        'received_amount' => 100,
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bkash',
        'received_amount' => 40,
        'payment_datetime' => '2026-10-04 10:00:00',
        'transaction_id' => 'TX-001',
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 60,
        'payment_datetime' => '2026-10-04 11:00:00',
        'transaction_id' => 'TX-002',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(2, 'data.0.payments')
        ->assertJsonPath('data.0.payments.0.payment_method', 'Bkash')
        ->assertJsonPath('data.0.payments.1.payment_method', 'Cash');
});

it('shows method totals across report filters independently of the selected payment method', function () {
    $matchingSale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Mixed Method Sale',
        'ticket_fee' => 1100,
        'total_payable' => 1100,
        'received_amount' => 1100,
    ]);
    Payment::create([
        'sales_id' => $matchingSale->id,
        'payment_method' => 'Bkash',
        'received_amount' => 1040,
    ]);
    Payment::create([
        'sales_id' => $matchingSale->id,
        'payment_method' => 'Cash',
        'received_amount' => 60,
    ]);

    $otherSale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Bank Transfer Sale',
        'ticket_fee' => 50,
        'total_payable' => 50,
        'received_amount' => 50,
        'bftn_status' => 'yes',
    ]);
    Payment::create([
        'sales_id' => $otherSale->id,
        'payment_method' => 'Bank Transfer',
        'received_amount' => 50,
    ]);
    $otherSale->bftn()->create(['received_status' => 1]);
    $nonBftnBankSale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Non-BFTN Bank Transfer Sale',
        'ticket_fee' => 25,
        'total_payable' => 25,
        'received_amount' => 25,
        'bftn_status' => 'no',
    ]);
    Payment::create([
        'sales_id' => $nonBftnBankSale->id,
        'payment_method' => 'Bank Transfer',
        'received_amount' => 25,
    ]);
    $nonBftnBankSale->bftn()->create(['received_status' => 0]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&payment_method=Bkash')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_received_amount', '1,100.00')
        ->assertJsonCount(3, 'totals.payment_method_totals')
        ->assertJsonPath('totals.payment_method_totals.0.payment_method', 'Bank Transfer')
        ->assertJsonPath('totals.payment_method_totals.0.amount', 50)
        ->assertJsonPath('totals.payment_method_totals.1.payment_method', 'Bkash')
        ->assertJsonPath('totals.payment_method_totals.1.amount', 1040)
        ->assertJsonPath('totals.payment_method_totals.2.payment_method', 'Cash')
        ->assertJsonPath('totals.payment_method_totals.2.amount', 60);
});

it('reports completed ticket refund allocations separately from extra payment refunds', function () {
    $sale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Refunded Report Customer',
        'ticket_fee' => 100,
        'total_payable' => 100,
        'received_amount' => 120,
        'due_amount' => 0,
    ]);
    $ticketRefund = Refund::create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refund_type' => 'partial',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 50,
        'gross_refund_amount' => 50,
        'other_fee_deduction' => 2,
        'refund_discount_amount' => 3,
        'due_adjusted_amount' => 5,
        'customer_refund_amount' => 35,
        'partner_share_amount' => 4,
        'company_retained_amount' => 1,
        'customer_refunded_at' => now(),
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $ticketRefund->id,
        'amount' => 35,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $extraRefund = Refund::create([
        'sales_id' => $sale->id,
        'status' => 'completed',
        'refund_type' => 'extra_payment',
        'refunded_number_of_tickets' => 0,
        'refunded_amount' => 20,
        'gross_refund_amount' => 20,
        'customer_refund_amount' => 20,
        'customer_refunded_at' => now(),
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $extraRefund->id,
        'amount' => 20,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=Refunded%20Report%20Customer')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_refunded_tickets', 1)
        ->assertJsonPath('totals.total_gross_refund_amount', '50.00')
        ->assertJsonPath('totals.total_other_fee_deduction', '2.00')
        ->assertJsonPath('totals.total_refund_discount_amount', '3.00')
        ->assertJsonPath('totals.total_due_adjusted_amount', '5.00')
        ->assertJsonPath('totals.total_customer_refund_paid', '35.00')
        ->assertJsonPath('totals.total_partner_share_amount', '4.00')
        ->assertJsonPath('totals.total_company_retained_amount', '1.00')
        ->assertJsonPath('totals.total_extra_refunded_amount', '20.00')
        ->assertJsonPath('totals.net_cash', '65.00')
        ->assertJsonPath('data.0.gross_refund_amount', 50)
        ->assertJsonPath('data.0.customer_refund_paid', 35)
        ->assertJsonPath('data.0.extra_refunded_amount', 20)
        ->assertJsonPath('data.0.net_cash', 61);
});

it('applies the report search term to summary totals as well as rows', function () {
    reportSale($this->ship, $this->company, ['customer_name' => 'Search Match', 'ticket_fee' => 100]);
    reportSale($this->ship, $this->company, ['customer_name' => 'Search Excluded', 'ticket_fee' => 900]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=Search%20Match')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_fee', '100.00');
});

it('accepts a null DataTables search value', function () {
    reportSale($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_fee', '300.00');
});

it('loads the report when DataTables omits the search value', function () {
    reportSale($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_fee', '300.00');
});

it('adjusts extra received amount into other fee', function () {
    $sale = reportSale($this->ship, $this->company, [
        'other_fee' => 10,
        'total_payable' => 310,
        'received_amount' => 350,
        'due_amount' => 0,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('extra-received.adjust', $sale), [])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Extra amount of 40 has been adjusted to Other Fee.');

    $sale->refresh();

    expect((float) $sale->other_fee)->toBe(50.0)
        ->and((float) $sale->total_payable)->toBe(350.0)
        ->and((float) $sale->due_amount)->toBe(0.0);
});

it('rejects adjusting a sale without extra received amount', function () {
    $sale = reportSale($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->postJson(route('extra-received.adjust', $sale), [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('creates a refund request for extra received amount', function () {
    $sale = reportSale($this->ship, $this->company, [
        'total_payable' => 300,
        'received_amount' => 350,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('extra-received.refund', $sale), [])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('refunds', [
        'sales_id' => $sale->id,
        'refund_type' => 'extra_payment',
        'refunded_number_of_tickets' => 0,
        'customer_refund_amount' => 50,
        'status' => 'requested',
    ]);
});

it('separates bftn pending and received report filters', function () {
    $pending = reportSale($this->ship, $this->company, ['bftn_status' => 'yes']);
    $received = reportSale($this->ship, $this->company, ['bftn_status' => 'yes']);
    Bftn::create(['sales_id' => $pending->id, 'received_status' => 0]);
    Bftn::create(['sales_id' => $received->id, 'received_status' => 1]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&bftn_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=2&start=0&length=10&bftn_status=received')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $received->id);
});
