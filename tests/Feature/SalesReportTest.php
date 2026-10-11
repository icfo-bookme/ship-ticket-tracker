<?php

use App\Models\CashCollection;
use App\Models\Category;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Models\Ship;
use App\Models\ShipPackage;
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
    $receivedAmount = (float) ($attributes['received_amount'] ?? 300);
    $ticketCount = (int) ($attributes['ticket_count'] ?? 2);
    $bftnStatus = $attributes['bftn_status'] ?? null;
    unset($attributes['received_amount'], $attributes['bftn_status'], $attributes['ticket_count']);

    $sale = ShipTicketSale::create(array_merge([
        'customer_name' => 'Report Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_value' => 300,
        'other_fee' => 0,
        'total_payable' => 300,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'status' => 'ticket-issued',
    ], $attributes));

    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Report package',
        'price' => 150,
        'round_trip_price' => 250,
    ]);
    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => $ticketCount,
        'type' => 'departure',
        'unit_price' => round((float) ($attributes['ticket_value'] ?? 300) / max($ticketCount, 1), 2),
    ]);

    if ($bftnStatus === null && $receivedAmount > 0) {
        Payment::create([
            'sales_id' => $sale->id,
            'payment_method' => 'Cash',
            'received_amount' => $receivedAmount,
        ]);
    }

    return $sale;
}

it('returns totals for the requested created date range', function () {
    reportSale($this->ship, $this->company);
    reportSale($this->ship, $this->company, ['ticket_count' => 1, 'ticket_value' => 200, 'total_payable' => 200, 'received_amount' => 200]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&start_create_date='.now()->subDays(6)->toDateString().'&end_create_date='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('totals.total_number_of_tickets', 3)
        ->assertJsonPath('totals.total_ticket_value', '500.00')
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

it('shows filter-scoped receipts and refunds before cashout in the report', function () {
    $filteredSale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Filtered Sale',
        'ticket_value' => 90,
        'total_payable' => 90,
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
    $refund = Refund::create([
        'sales_id' => $filteredSale->id,
        'status' => 'completed',
        'refunded_number_of_tickets' => 0,
        'refund_type' => 'partial',
        'refunded_amount' => 15,
        'customer_refund_amount' => 10,
        'partner_share_amount' => 5,
        'due_adjusted_amount' => 40,
    ]);
    RefundCustomerPayment::create([
        'refund_id' => $refund->id,
        'amount' => 10,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=Filtered%20Sale')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_received_amount', '120.00')
        ->assertJsonPath('totals.total_extra_received_amount', '30.00')
        ->assertJsonPath('totals.report_refund_outflow', '15.00')
        ->assertJsonPath('totals.report_net_before_cashout', '105.00');
});

it('preselects the last seven days on the report page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('id="startCreateDate" value="'.now()->subDays(6)->toDateString().'"', false)
        ->assertSee('id="endCreateDate" value="'.now()->toDateString().'"', false);
});

it('renders the sales report filters and summary targets', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('id="salesTable"', false)
        ->assertSee('&quot;pageLength&quot;:10', false)
        ->assertSee('id="saleReportDetailModal"', false)
        ->assertSee('data-close-modal="saleReportDetailModal"', false)
        ->assertDontSee('data-modal-hide="saleReportDetailModal"', false)
        ->assertSee('Ticket Price / Other Fee / Discount')
        ->assertSee('Received Amount')
        ->assertSee('id="totalSellTickets"', false)
        ->assertSee('id="totalCompletedTicketRefundAmount"', false)
        ->assertSee('Gross Ticket Refund')
        ->assertSee('Net Ticket Refund')
        ->assertSee('Refunds &amp; Adjustments', false)
        ->assertSee('BFTN Settlement')
        ->assertSee('Filtered Cash Summary')
        ->assertSee('id="netSalesAmount"', false)
        ->assertSee('Sales After Ticket Refunds')
        ->assertDontSee('Ticket Sales After Discount = Ticket Value')
        ->assertDontSee('Available Net Cash is global and is not affected by the report filters.')
        ->assertSee('id="totalExtraRefundedAmount"', false)
        ->assertSee('Extra Payment Refund Paid')
        ->assertSee('id="totalDueAdjustedAmount"', false)
        ->assertSee('id="totalCompanyRetainedAmount"', false)
        ->assertSee('id="totalBftnPendingAmount"', false)
        ->assertSee('id="totalBftnReceivedAmount"', false)
        ->assertSee('id="totalReceivedAmount"', false)
        ->assertSee('id="totalExtraReceivedAmount"', false)
        ->assertSee('Extra Payment Received (Included in Total Received)')
        ->assertSee('id="reportRefundOutflow"', false)
        ->assertSee('Refund Outflow (Customer + Partner + Extra Refunds)')
        ->assertDontSee('id="cashCollectionTotalCashout"', false)
        ->assertSee('id="totalCustomerRefundPaid"', false)
        ->assertSee('id="totalPartnerShareAmount"', false)
        ->assertSee('id="totalRefundDiscountAmount"', false)
        ->assertSee('id="reportNetBeforeCashout"', false)
        ->assertSee('Net Receipts Before Cashout')
        ->assertDontSee('Completed Ticket Refunds')
        ->assertSee('id="toggleAdvancedFilters"', false)
        ->assertSee('id="advancedReportFilters"', false)
        ->assertSee('More filters')
        ->assertSee('id="payment_method"', false)
        ->assertDontSee('id="bftnFilter"', false)
        ->assertDontSee('id="totalBftnAmount"', false);

    expect(strpos($response->getContent(), 'id="salesTable"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="totalSellTickets"'))
        ->and(strpos($response->getContent(), 'id="totalBftnReceivedAmount"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="reportRefundOutflow"'))
        ->and(strpos($response->getContent(), 'id="totalReceivedAmount"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="reportRefundOutflow"'))
        ->and(strpos($response->getContent(), 'id="reportRefundOutflow"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="reportNetBeforeCashout"'))
        ->and(strpos($response->getContent(), 'border-emerald-300 bg-emerald-50'))
        ->toBeLessThan(strpos($response->getContent(), 'id="reportNetBeforeCashout"'));
});

it('includes every payment record in the sale report details payload', function () {
    $sale = reportSale($this->ship, $this->company, [
        'ticket_value' => 100,
        'total_payable' => 100,
        'received_amount' => 100,
    ]);
    $sale->payments()->delete();
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
        'ticket_value' => 1100,
        'total_payable' => 1100,
        'received_amount' => 1100,
    ]);
    $matchingSale->payments()->delete();
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
        'ticket_value' => 50,
        'total_payable' => 50,
        'received_amount' => 50,
        'bftn_status' => 'yes',
    ]);
    $otherSale->payments()->delete();
    $bftnPayment = Payment::create([
        'sales_id' => $otherSale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'received',
        'received_amount' => 50,
    ]);
    $nonBftnBankSale = reportSale($this->ship, $this->company, [
        'customer_name' => 'Non-BFTN Bank Transfer Sale',
        'ticket_value' => 25,
        'total_payable' => 25,
        'received_amount' => 25,
        'bftn_status' => 'no',
    ]);
    Payment::create([
        'sales_id' => $nonBftnBankSale->id,
        'payment_method' => 'Bank Transfer',
        'received_amount' => 25,
    ]);

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
        'ticket_value' => 100,
        'discount_amount' => 10,
        'total_payable' => 90,
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
        ->assertJsonPath('totals.total_ticket_sales_after_discount', '90.00')
        ->assertJsonPath('totals.total_gross_ticket_refund_amount', '50.00')
        ->assertJsonPath('totals.total_completed_ticket_refund_amount', '47.00')
        ->assertJsonPath('totals.total_refund_discount_amount', '3.00')
        ->assertJsonPath('totals.total_due_adjusted_amount', '5.00')
        ->assertJsonPath('totals.total_customer_refund_paid', '35.00')
        ->assertJsonPath('totals.total_partner_share_amount', '4.00')
        ->assertJsonPath('totals.total_company_retained_amount', '1.00')
        ->assertJsonPath('totals.total_extra_refunded_amount', '20.00')
        ->assertJsonPath('totals.net_sales_amount', '43.00')
        ->assertJsonPath('totals.report_refund_outflow', '59.00')
        ->assertJsonPath('totals.report_net_before_cashout', '61.00')
        ->assertJsonPath('data.0.gross_refund_amount', 50)
        ->assertJsonPath('data.0.discount_amount', '10.00')
        ->assertJsonPath('data.0.refunded_amount', 47)
        ->assertJsonPath('data.0.due_adjusted_amount', 5)
        ->assertJsonPath('data.0.customer_refund_paid', 35)
        ->assertJsonPath('data.0.extra_refunded_amount', 20)
        ->assertJsonPath('data.0.net_cash', 61);
});

it('applies the report search term to summary totals as well as rows', function () {
    reportSale($this->ship, $this->company, ['customer_name' => 'Search Match', 'ticket_value' => 100]);
    reportSale($this->ship, $this->company, ['customer_name' => 'Search Excluded', 'ticket_value' => 900]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=Search%20Match')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_value', '100.00');
});

it('accepts a null DataTables search value', function () {
    reportSale($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&search[value]=')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_value', '300.00');
});

it('loads the report when DataTables omits the search value', function () {
    reportSale($this->ship, $this->company);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('totals.total_ticket_value', '300.00');
});

it('ignores unsupported DataTables sort directions instead of treating them as SQL', function () {
    $ascending = reportSale($this->ship, $this->company, [
        'customer_name' => 'A Customer',
    ]);
    $descending = reportSale($this->ship, $this->company, [
        'customer_name' => 'Z Customer',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&order[0][column]=1&order[0][dir]=desc%2C%20(SELECT%201)')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ascending->id)
        ->assertJsonPath('data.1.id', $descending->id);
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
    $pending = reportSale($this->ship, $this->company, [
        'bftn_status' => 'yes',
        'received_amount' => 125,
    ]);
    $received = reportSale($this->ship, $this->company, [
        'bftn_status' => 'yes',
        'received_amount' => 275,
    ]);
    $pendingPayment = Payment::create([
        'sales_id' => $pending->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 125,
    ]);
    $receivedPayment = Payment::create([
        'sales_id' => $received->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'received',
        'received_amount' => 275,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('totals.total_bftn_pending_amount', '125.00')
        ->assertJsonPath('totals.total_bftn_received_amount', '275.00');

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=2&start=0&length=10&bftn_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=3&start=0&length=10&bftn_status=received')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $received->id);
});

it('reports pending BFTN separately without counting it as due or received', function () {
    $sale = reportSale($this->ship, $this->company, [
        'ticket_value' => 100,
        'total_payable' => 100,
        'received_amount' => 0,
        'due_amount' => 0,
        'bftn_status' => 'yes',
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 100,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonPath('data.0.received_amount', 0)
        ->assertJsonPath('data.0.due_amount', '0.00')
        ->assertJsonPath('totals.total_received_amount', '0.00')
        ->assertJsonPath('totals.total_due_amount', '0.00')
        ->assertJsonPath('totals.total_bftn_pending_amount', '100.00');
});
