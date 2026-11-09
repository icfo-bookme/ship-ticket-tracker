<?php

use App\Models\Category;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SalesSource;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Finance\CashCollectionService;
use App\Services\Refunds\RefundService;
use App\Services\Reports\BftnReportService;
use App\Services\Reports\RefundReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\TicketIssuedReportService;
use App\Services\Sales\ShipTicketSaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(Permission::findOrCreate('sales.create', 'web'));
    $this->actingAs($this->user);
    $this->ship = Ship::create(['name' => 'Adjustment Ship', 'status' => 1]);
    $this->package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Standard', 'price' => 1000]);
    $this->source = SalesSource::create(['name' => 'Direct adjustment', 'is_active' => true, 'sort_order' => 1]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Refund Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $this->ship->id, 'issued_date' => now()->toDateString(),
        'total_payable' => 1000, 'other_fee' => 0, 'discount_amount' => 0,
        'status' => 'ticket-issued', 'sold_by' => $this->user->id,
    ]);
    Category::create(['ticket_id' => $this->sale->id, 'package_id' => $this->package->id, 'type' => 'departure', 'quantity' => 1, 'unit_price' => 1000]);
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 1000, 'is_bftn' => false, 'settlement_status' => 'received', 'paid_date' => now()->toDateString()]);
    $this->refund = Refund::create([
        'sales_id' => $this->sale->id, 'status' => 'partner_approved', 'refund_type' => 'partial',
        'refunded_number_of_tickets' => 1, 'refunded_amount' => 1000, 'gross_refund_amount' => 1000,
        'customer_charge_amount' => 200, 'partner_share_amount' => 0, 'company_retained_amount' => 200,
        'customer_refund_amount' => 800, 'requested_at' => now(),
    ]);
    $this->payload = [
        'customer_name' => 'Refund Customer', 'customer_mobile' => '01712345678', 'whatsapp' => '01712345678',
        'ship_id' => $this->ship->id, 'sales_source_id' => $this->source->id,
        'collect_from_office' => 1, 'issued_date' => now()->toDateString(),
        'journey_date' => now()->addWeek()->toDateString(), 'sold_by' => (string) $this->user->id,
        'ticket_categories' => ['departure' => [['package_id' => $this->package->id, 'quantity' => 1]]],
        'refund_id' => $this->refund->id, 'refund_adjustment_amount' => 800,
        'payment_methods' => [['method' => 'Cash', 'amount' => 200]],
    ];
});

it('creates a new sale using refund credit and an additional payment', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    $payment = $sale->payments()->whereNotNull('refund_id')->firstOrFail();
    expect($payment->payment_method)->toBe(Payment::REFUND_ADJUSTED)
        ->and($payment->refund_id)->toBe($this->refund->id)
        ->and($payment->adjusted_by)->toBe($this->user->id)
        ->and((float) $sale->due_amount)->toBe(0.0)
        ->and((float) $sale->payments()->sum('received_amount'))->toBe(1000.0)
        ->and($this->refund->fresh()->adjustment_started_at)->not->toBeNull();
});

it('supports an entirely refund funded sale with an empty default payment row', function () {
    $this->package->update(['price' => 500]);
    $this->payload['refund_adjustment_amount'] = 500;
    $this->payload['payment_methods'] = [['method' => '', 'amount' => 0]];
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    expect($sale->payments()->count())->toBe(1)->and((float) $sale->due_amount)->toBe(0.0);
});

it('deducts the old sale due once before offering the refund balance', function () {
    $this->sale->payments()->update(['received_amount' => 600]);
    $this->getJson(route('ship-ticket-sales.refund-adjustments', ['refund_id' => $this->refund->id]))
        ->assertOk()->assertJsonPath('data.0.available_amount', 400);
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $this->getJson(route('ship-ticket-sales.refund-adjustments', ['refund_id' => $this->refund->id]))
        ->assertOk()->assertJsonPath('data.0.available_amount', 100);
    expect((float) $this->refund->fresh()->due_adjusted_amount)->toBe(400.0)
        ->and((float) $this->sale->fresh()->due_amount)->toBe(0.0)
        ->and(ShipTicketSale::query()->withDue()->whereKey($this->sale->id)->exists())->toBeFalse();
});

it('rejects unavailable refunds and rolls back the new sale and payments', function (string $status) {
    $this->refund->update(['status' => $status]);
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_id');
    expect(ShipTicketSale::count())->toBe(1)->and(Payment::count())->toBe(1);
})->with(['requested', 'completed', 'cancelled']);

it('does not reuse an exhausted refund', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_id');
    expect(ShipTicketSale::count())->toBe(2)->and(Payment::count())->toBe(3);
});

it('rejects adjustment above the new sale payable', function () {
    $this->refund->update(['customer_refund_amount' => 2000]);
    $this->payload['refund_adjustment_amount'] = 1001;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_adjustment_amount');
});

it('requires an amount and prevents fabricated adjustment payment rows', function () {
    unset($this->payload['refund_adjustment_amount']);
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_adjustment_amount');
    unset($this->payload['refund_id']);
    $this->payload['payment_methods'][0]['method'] = Payment::REFUND_ADJUSTED;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('payment_methods.0.method');
});

it('shows requested refunds as awaiting approval and protects the search endpoint', function () {
    $this->refund->update(['status' => 'requested']);
    $this->getJson(route('ship-ticket-sales.refund-adjustments', ['search' => '01712345678']))
        ->assertOk()->assertJsonPath('data.0.can_adjust', false);
    $this->actingAs(User::factory()->create())->getJson(route('ship-ticket-sales.refund-adjustments'))->assertForbidden();
});

it('only pays the remaining refund balance in cash after a partial adjustment', function () {
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    app(RefundService::class)->refundCustomer($this->refund->fresh(), ['payment_method' => 'Cash']);
    expect((float) $this->refund->customerPayments()->sum('amount'))->toBe(500.0)
        ->and($this->refund->fresh()->status)->toBe('completed');
    $this->getJson(route('ship-ticket-sales.refund-adjustments'))->assertJsonCount(0, 'data');
});

it('reports transfers separately from cash and keeps the remaining refund balance', function () {
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    ShipTicketSale::latest('id')->first()->update(['status' => 'ticket-issued']);
    $report = app(RefundReportService::class)->dataTable(Request::create('/'));
    expect($report['totals']['refund_adjusted_amount'])->toBe('300.00')
        ->and($report['totals']['remaining_refund_amount'])->toBe('500.00')
        ->and($report['totals']['customer_refund_paid'])->toBe('0.00')
        ->and(app(CashCollectionService::class)->summary()['totalReceivedAmount'])->toBe(1200.0);
    $salesReport = app(SalesReportService::class)->dataTable(Request::create('/'));
    expect($salesReport['totals']['total_received_amount'])->toBe('1,500.00')
        ->and($salesReport['totals']['report_net_before_cashout'])->toBe('1,200.00');
});

it('does not pay adjusted money twice when completing a fully transferred refund', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    expect($this->refund->fresh()->status)->toBe('completed')
        ->and(fn () => app(RefundService::class)->refundCustomer($this->refund->fresh(), []))->toThrow(ValidationException::class);
    expect($this->refund->customerPayments()->count())->toBe(0);
    ShipTicketSale::latest('id')->first()->update(['status' => 'ticket-issued']);
    expect(app(CashCollectionService::class)->summary()['availableCashAmount'])->toBe(1200.0);
});

it('lists a fully adjusted refund under completed refunds with its new sale reference', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $newSale = ShipTicketSale::latest('id')->first();
    $this->user->givePermissionTo(Permission::findOrCreate('refunds.view', 'web'));
    $this->getJson(route('refunds.requests.data', ['status' => 'completed']))->assertOk()
        ->assertJsonPath('data.0.status', 'completed')
        ->assertJsonPath('data.0.settlement_label', 'Refund Adjusted')
        ->assertJsonPath('data.0.refund_adjusted_amount', 800)
        ->assertJsonPath('data.0.customer_refund_paid', 0)
        ->assertJsonPath('data.0.adjustment_references.0.sale_id', $newSale->id);
    $this->getJson(route('refunds.requests.data', ['status' => 'partner_approved']))->assertJsonCount(0, 'data');
    $this->get('/refunded')->assertOk()->assertSee('Settlement / New Sale');
    $this->get(route('refunds.details', $this->sale->id))->assertOk()
        ->assertSee('Refund Adjusted')->assertSee('Sale #'.$newSale->id);
    expect($this->refund->fresh()->customer_refunded_at)->toBeNull();
});

it('keeps partial transfers open and identifies mixed settlements after the remaining cash is paid', function () {
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    expect($this->refund->fresh()->status)->toBe('partner_approved');
    app(RefundService::class)->refundCustomer($this->refund->fresh(), ['payment_method' => 'Cash']);
    $summary = $this->refund->fresh()->settlementSummary();
    expect($summary['settlement_label'])->toBe('Partly Refunded & Adjusted')
        ->and($summary['customer_refund_paid'])->toBe(500.0)
        ->and($summary['refund_adjusted_amount'])->toBe(300.0)
        ->and($summary['remaining_refund_amount'])->toBe(0.0);
});

it('identifies cash refunds and due-only settlements separately', function () {
    app(RefundService::class)->refundCustomer($this->refund, ['payment_method' => 'Cash']);
    expect($this->refund->fresh()->settlementSummary()['settlement_label'])->toBe('Refunded');
    $dueRefund = $this->refund->replicate();
    $dueRefund->customer_refund_amount = 0;
    $dueRefund->due_adjusted_amount = 800;
    $dueRefund->save();
    expect($dueRefund->settlementSummary()['settlement_label'])->toBe('Due Adjusted');
});

it('completes historical exhausted adjustments without closing partial refunds or creating cash payments', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $this->refund->refresh()->update(['status' => 'partner_approved']);
    $partial = $this->refund->replicate();
    $partial->customer_refund_amount = 1000;
    $partial->save();
    $payment = Payment::query()->where('refund_id', $this->refund->id)->firstOrFail()->replicate();
    $payment->refund_id = $partial->id;
    $payment->save();

    $migration = require database_path('migrations/2026_11_09_074137_complete_fully_adjusted_refunds.php');
    $migration->up();
    $migration->up();

    expect($this->refund->fresh()->status)->toBe('completed')
        ->and($partial->fresh()->status)->toBe('partner_approved')
        ->and($this->refund->customerPayments()->count())->toBe(0)
        ->and($this->refund->fresh()->customer_refunded_at)->toBeNull();
});

it('accounts for extra payment refunds transferred into a new sale', function () {
    $this->sale->update(['total_payable' => 200]);
    $this->refund->update(['refund_type' => 'extra_payment', 'gross_refund_amount' => 800]);
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $summary = app(\App\Services\Finance\ExtraPaymentService::class)->summary($this->sale->fresh());
    expect($summary['extra_refunded_amount'])->toBe(300.0)
        ->and($summary['extra_refund_pending_amount'])->toBe(500.0);
});

it('prevents cancellation or deletion of a refund after its credit is used', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    expect(fn () => app(RefundService::class)->cancel($this->refund->fresh()))->toThrow(ValidationException::class)
        ->and(fn () => app(RefundService::class)->delete($this->refund->fresh()))->toThrow(ValidationException::class)
        ->and(fn () => app(ShipTicketSaleService::class)->delete(ShipTicketSale::latest('id')->value('id')))->toThrow(ValidationException::class);
});

it('keeps refund payments when editing other sale information and rejects changes to the credit', function () {
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    $payment = $sale->payments()->whereNotNull('refund_id')->firstOrFail();
    $company = \App\Models\Company::create(['name' => 'Adjustment Company']);
    $data = [
        'customer_name' => 'Updated customer', 'customer_mobile' => '01712345678', 'whatsapp' => '01712345678',
        'email' => null, 'nid' => null, 'date_of_birth' => null, 'collect_from_office' => true,
        'ship_id' => $this->ship->id, 'company_id' => $company->id, 'journey_date' => now()->addWeek()->toDateString(),
        'return_date' => null, 'sales_source' => 'Direct adjustment', 'issued_date' => now()->toDateString(),
        'remark1' => null, 'remark2' => null,
    ];
    app(ShipTicketSaleService::class)->update($sale, $data, $data);
    expect($payment->fresh())->not->toBeNull()
        ->and((float) $sale->fresh()->payments()->sum('received_amount'))->toBe(800.0);
    $data['payments'] = [['payment_id' => $payment->id, 'payment_method' => Payment::REFUND_ADJUSTED, 'received_amount' => 801]];
    expect(fn () => app(ShipTicketSaleService::class)->update($sale, $data, $data))->toThrow(ValidationException::class);
});

it('renders the refund selector and the separate refund report columns', function () {
    $this->get(route('ship-ticket-sales.create'))->assertOk()->assertSee('Adjust from refund')->assertSee('refund_adjustment_amount')
        ->assertSee('id="refund_id"', false)
        ->assertDontSee('id="refundAdjustmentSearch"', false)
        ->assertDontSee('id="searchRefundAdjustments"', false);
    $this->user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));
    $this->get(route('refund-reports.index'))->assertOk()->assertSee('Adjusted to Sales')->assertSee('Remaining Balance');
});

function refundAdjustmentEditPayload(ShipTicketSale $sale, int $refundId, float $amount): array
{
    $company = \App\Models\Company::create(['name' => 'Edit adjustment company']);

    return [
        'customer_name' => $sale->customer_name, 'customer_mobile' => $sale->customer_mobile,
        'whatsapp' => '01712345678', 'email' => null, 'nid' => null, 'date_of_birth' => null,
        'collect_from_office' => 1, 'ship_id' => $sale->ship_id, 'company_id' => $company->id,
        'journey_date' => now()->addWeek()->toDateString(), 'return_date' => null,
        'sales_source' => 'Direct adjustment', 'issued_date' => now()->toDateString(),
        'status' => $sale->status, 'remark1' => null, 'remark2' => null,
        'refund_id' => $refundId, 'refund_adjustment_amount' => $amount,
        'payments' => $sale->payments->map(fn (Payment $payment): array => [
            'payment_id' => $payment->id, 'payment_method' => $payment->payment_method,
            'received_amount' => $payment->received_amount,
        ])->all(),
    ];
}

it('lets an editor add refund credit to an existing sale without create permission', function () {
    unset($this->payload['refund_id'], $this->payload['refund_adjustment_amount']);
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    $this->user->revokePermissionTo('sales.create');
    $this->user->givePermissionTo(Permission::findOrCreate('sales.edit', 'web'), Permission::findOrCreate('sales.view', 'web'));

    $this->getJson(route('ship-ticket-sales.refund-adjustments'))->assertOk();
    $this->get(route('ship-ticket-sales.show', $sale))->assertOk()->assertSee('Adjust from refund');
    $this->putJson(route('ship-ticket-sales.update', $sale), refundAdjustmentEditPayload($sale, $this->refund->id, 800))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect((float) $sale->fresh()->due_amount)->toBe(0.0)
        ->and((float) $sale->payments()->sum('received_amount'))->toBe(1000.0)
        ->and($this->refund->fresh()->status)->toBe('completed');
    $this->get(route('ship-ticket-sales.show', $sale))->assertOk()->assertSee('Refund Adjusted');
});

it('rejects edit adjustments above the remaining due and rolls back payment edits', function () {
    unset($this->payload['refund_id'], $this->payload['refund_adjustment_amount']);
    $this->payload['payment_methods'][0]['amount'] = 500;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    $this->user->givePermissionTo(Permission::findOrCreate('sales.edit', 'web'));
    $data = refundAdjustmentEditPayload($sale, $this->refund->id, 501);
    $this->putJson(route('ship-ticket-sales.update', $sale), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_adjustment_amount');
    expect((float) $sale->fresh()->due_amount)->toBe(500.0)
        ->and($sale->payments()->whereNotNull('refund_id')->count())->toBe(0)
        ->and($this->refund->fresh()->adjustment_started_at)->toBeNull();
});

it('preserves previous credits when adding a second refund adjustment from edit', function () {
    $this->payload['refund_adjustment_amount'] = 300;
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $sale = ShipTicketSale::latest('id')->first();
    $existing = $sale->payments()->whereNotNull('refund_id')->firstOrFail();
    $this->user->givePermissionTo(Permission::findOrCreate('sales.edit', 'web'));
    $this->putJson(route('ship-ticket-sales.update', $sale), refundAdjustmentEditPayload($sale, $this->refund->id, 500))
        ->assertRedirect()->assertSessionHasNoErrors();
    expect((float) $sale->fresh()->due_amount)->toBe(0.0)
        ->and((float) $existing->fresh()->received_amount)->toBe(300.0)
        ->and($sale->payments()->whereNotNull('refund_id')->count())->toBe(2)
        ->and($this->refund->fresh()->status)->toBe('completed');
});

it('requires a matching refund amount on edit and rejects self adjustment', function () {
    $this->user->givePermissionTo(Permission::findOrCreate('sales.edit', 'web'));
    $data = refundAdjustmentEditPayload($this->sale, $this->refund->id, 100);
    unset($data['refund_adjustment_amount']);
    $this->putJson(route('ship-ticket-sales.update', $this->sale), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_adjustment_amount');
    $data['refund_adjustment_amount'] = 100;
    $this->putJson(route('ship-ticket-sales.update', $this->sale), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('refund_id');
});

it('includes older available refunds in the dropdown beyond the first fifty records', function () {
    foreach (range(1, 50) as $index) {
        $this->refund->replicate()->save();
    }

    $response = $this->getJson(route('ship-ticket-sales.refund-adjustments'))->assertOk()->assertJsonCount(51, 'data');

    expect(collect($response->json('data'))->pluck('id'))->toContain($this->refund->id);
});

it('reconciles refund adjustments across every financial report', function (array $scenario) {
    $discount = $scenario['discount'] ?? 0;
    $oldPaid = $scenario['old_paid'] ?? 1000;
    $dueAdjusted = max(1000 - $discount - $oldPaid, 0);
    $credit = 800 - $discount - $dueAdjusted;
    $adjusted = $scenario['adjusted'] ?? $credit;
    $bftn = $scenario['bftn'] ?? 0;
    $pending = ($scenario['bftn_pending'] ?? false) ? $bftn : 0;
    $cash = 1200 - $adjusted - $bftn;
    $partnerShare = $scenario['partner_share'] ?? 0;
    $cashRefund = ($scenario['pay_remainder'] ?? false) ? $credit - $adjusted : 0;
    $remaining = $credit - $adjusted - $cashRefund;
    $actualCash = $oldPaid + $cash + $bftn - $pending - $cashRefund - $partnerShare;
    $expectedAvailableCash = $actualCash + ($remaining > 0 ? $partnerShare : 0);
    $received = $oldPaid + 1200 - $pending;

    $this->sale->update(['discount_amount' => $discount, 'total_payable' => 1000 - $discount]);
    $this->sale->payments()->update(['received_amount' => $oldPaid]);
    $this->refund->update([
        'customer_refund_amount' => 800 - $discount, 'refund_discount_amount' => $discount,
        'partner_share_amount' => $partnerShare, 'company_retained_amount' => 200 - $partnerShare,
    ]);
    $this->package->update(['price' => 1200]);
    $this->payload['refund_adjustment_amount'] = $adjusted;
    $this->payload['payment_methods'] = [['method' => 'Cash', 'amount' => $cash]];
    if ($bftn > 0) {
        $this->payload['payment_methods'][] = ['method' => 'Bank Transfer', 'amount' => $bftn, 'is_bftn' => true, 'bftn_issue_datetime' => now()->toDateTimeString()];
    }
    $this->postJson(route('ship-ticket-sales.store'), $this->payload)->assertRedirect();
    $newSale = ShipTicketSale::latest('id')->first();
    $newSale->update(['status' => 'ticket-issued']);
    if ($bftn > 0 && $pending === 0) {
        $newSale->payments()->where('is_bftn', true)->update(['settlement_status' => 'received', 'received_at' => now()]);
    }
    if ($cashRefund > 0) {
        app(RefundService::class)->refundCustomer($this->refund->fresh(), ['payment_method' => 'Cash']);
    }
    foreach ([$this->sale, $newSale] as $sale) {
        $sale->verifyby()->create(['name' => 'ticket-issued', 'verified_by' => $this->user->id]);
    }

    $sales = app(SalesReportService::class)->dataTable(Request::create('/'));
    $refunds = app(RefundReportService::class)->dataTable(Request::create('/'));
    $issued = app(TicketIssuedReportService::class)->dataTable([]);
    $bftns = app(BftnReportService::class)->dataTable(Request::create('/'));
    $availableCash = app(CashCollectionService::class)->summary();
    $dashboard = app(DashboardMetricsService::class)->metrics();

    expect($sales['totals']['net_sales_amount'])->toBe('1,200.00')
        ->and($sales['totals']['total_ticket_value'])->toBe('2,200.00')
        ->and($sales['totals']['total_completed_ticket_refund_amount'])->toBe(number_format(1000 - $discount, 2))
        ->and($sales['totals']['total_received_amount'])->toBe(number_format($received, 2))
        ->and($sales['totals']['report_net_before_cashout'])->toBe(number_format($actualCash, 2))
        ->and((float) $sales['data']->sum('net_cash'))->toBe((float) $actualCash)
        ->and($sales['totals']['total_due_amount'])->toBe('0.00')
        ->and($refunds['totals']['refund_adjusted_amount'])->toBe(number_format($adjusted, 2, '.', ''))
        ->and($refunds['totals']['customer_refund_paid'])->toBe(number_format($cashRefund, 2, '.', ''))
        ->and($refunds['totals']['remaining_refund_amount'])->toBe(number_format($remaining, 2, '.', ''))
        ->and($refunds['totals']['due_adjusted_amount'])->toBe(number_format($dueAdjusted, 2, '.', ''))
        ->and($issued['totals']['total_sales'])->toBe(2)
        ->and($issued['totals']['total_ticket_value'])->toBe(2200.0)
        ->and($issued['totals']['total_received_amount'])->toBe((float) $received)
        ->and($issued['totals']['total_due_amount'])->toBe(0.0)
        ->and($bftns['totals']['total_amount'])->toBe(number_format($bftn, 2, '.', ''))
        ->and($bftns['totals']['pending_amount'])->toBe(number_format($pending, 2, '.', ''))
        ->and($availableCash['availableCashAmount'])->toBe((float) $expectedAvailableCash)
        ->and((float) $dashboard['totalDue'])->toBe(0.0);
})->with([
    'fully adjusted' => [[]],
    'mixed cash refund and adjustment' => [['adjusted' => 300, 'pay_remainder' => true]],
    'partial adjustment with unpaid remainder' => [['adjusted' => 300]],
    'old due deducted first' => [['old_paid' => 600]],
    'old sale discount' => [['discount' => 100, 'old_paid' => 900]],
    'new pending BFTN' => [['bftn' => 200, 'bftn_pending' => true]],
    'new received BFTN' => [['bftn' => 200]],
    'partner fee with full adjustment' => [['partner_share' => 50]],
    'partner fee with partial adjustment' => [['partner_share' => 50, 'adjusted' => 300]],
]);
