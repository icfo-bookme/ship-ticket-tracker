<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyErrorRefund;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Refunds\RefundService;
use Database\Seeders\CompanyErrorRefundSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    foreach (['refunds.create', 'refunds.edit', 'refunds.cancel', 'refunds.view', 'refunds.approve', 'refunds.payment_details', 'refunds.customer_payment', 'reports.view', 'sales.view', 'sales.audit.view'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($this->user);
    $this->ship = Ship::create(['name' => 'Loss Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Loss Company', 'status' => 1]);
    $package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Standard', 'price' => 500]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Company Error Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $this->ship->id, 'company_id' => $this->company->id,
        'status' => 'ticket-issued', 'issued_date' => now()->toDateString(),
        'total_payable' => 1000, 'discount_amount' => 0, 'other_fee' => 0,
    ]);
    $category = $this->sale->categories()->create([
        'package_id' => $package->id, 'quantity' => 2, 'unit_price' => 500, 'type' => 'departure',
    ]);
    $this->sale->payments()->create([
        'payment_method' => 'Cash', 'received_amount' => 1000, 'is_bftn' => false, 'settlement_status' => 'received',
    ]);
    $this->payload = [
        'refunded_number_of_tickets' => 2, 'refunded_amount' => 1000,
        'ticket_selections' => [['category_id' => $category->id, 'refunded_quantity' => 2]],
        'customer_charge_percent' => 0, 'partner_share_percent' => 20,
        'is_company_error' => true, 'company_loss_type' => 'partner_share',
        'company_error_reason' => 'Incorrect booking by company staff.',
    ];
});

it('refunds the customer in full while recording the partner share as company loss', function () {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $loss = $refund->companyErrorRefund;
    expect($refund->customer_refund_amount)->toBe('1000.00')
        ->and($refund->gross_refund_amount)->toBe('1000.00')
        ->and($refund->partner_share_amount)->toBe('200.00')
        ->and($refund->company_retained_amount)->toBe('0.00')
        ->and($loss->loss_amount)->toBe('200.00')
        ->and($loss->created_by)->toBe($this->user->id);
    $log = AuditLog::query()->where('auditable_type', Refund::class)->sole();
    expect($log->new_values['company_error'][0]['loss_amount'])->toBe('200.00')
        ->and($log->sale_id)->toBe($this->sale->id)
        ->and($log->user_id)->toBe($this->user->id);
    $this->getJson(route('refunds.requests.data'))->assertOk()
        ->assertJsonPath('data.0.company_error_refund.loss_amount', '200.00');
});

it('calculates custom loss using only the selected ticket value', function (int $percent, string $expected) {
    $payload = [...$this->payload, 'company_loss_type' => 'custom', 'company_loss_percent' => $percent, 'partner_share_percent' => 0];
    $payload['ticket_selections'][0]['refunded_quantity'] = 1;
    $this->postJson(route('refunds.partial', $this->sale), $payload)->assertOk();
    $refund = Refund::query()->sole();
    expect($refund->gross_refund_amount)->toBe('500.00')
        ->and($refund->customer_refund_amount)->toBe('500.00')
        ->and($refund->companyErrorRefund->loss_amount)->toBe($expected);
})->with([[20, '100.00'], [100, '500.00']]);

it('keeps normal refunds unchanged and ignores unchecked company fields', function () {
    $this->postJson(route('refunds.partial', $this->sale), [
        ...$this->payload, 'is_company_error' => false,
        'customer_charge_percent' => 20, 'company_loss_type' => 'invalid',
    ])->assertOk();
    expect(CompanyErrorRefund::query()->count())->toBe(0)
        ->and(Refund::query()->sole()->customer_refund_amount)->toBe('800.00');
});

it('validates company error fields without writing any refund', function (array $changes, string $field) {
    $this->postJson(route('refunds.partial', $this->sale), [...$this->payload, ...$changes])
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(Refund::query()->count())->toBe(0)->and(CompanyErrorRefund::query()->count())->toBe(0);
})->with([
    'missing reason' => [['company_error_reason' => ''], 'company_error_reason'],
    'invalid type' => [['company_loss_type' => 'invalid'], 'company_loss_type'],
    'missing percentage' => [['company_loss_type' => 'custom'], 'company_loss_percent'],
    'over 100' => [['company_loss_type' => 'custom', 'company_loss_percent' => 101], 'company_loss_percent'],
    'negative' => [['company_loss_type' => 'custom', 'company_loss_percent' => -1], 'company_loss_percent'],
    'normal partner exceeds charge' => [['is_company_error' => false], 'partner_share_percent'],
    'uncovered partner loss' => [['company_loss_type' => 'custom', 'company_loss_percent' => 10], 'company_loss_percent'],
]);

it('updates and removes company loss with before and after audit history', function () {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $lossId = $refund->companyErrorRefund->id;
    $payload = [...$this->payload, 'company_loss_type' => 'custom', 'company_loss_percent' => 100];
    $this->putJson(route('refunds.update', $refund), $payload)->assertOk();
    expect($refund->fresh()->companyErrorRefund->id)->toBe($lossId)
        ->and($refund->fresh()->companyErrorRefund->loss_amount)->toBe('1000.00');
    $log = AuditLog::query()->latest('id')->first();
    expect($log->old_values['company_error'][0]['loss_amount'])->toBe('200.00')
        ->and($log->new_values['company_error'][0]['loss_amount'])->toBe('1000.00');
    $this->putJson(route('refunds.update', $refund), [...$payload, 'is_company_error' => false, 'partner_share_percent' => 0])->assertOk();
    expect(CompanyErrorRefund::query()->count())->toBe(0);
    $log = AuditLog::query()->latest('id')->first();
    expect($log->old_values['company_error'][0]['loss_amount'])->toBe('1000.00')
        ->and($log->new_values['company_error'])->toBe([]);
});

it('cancels the refund and deletes its loss while retaining the audited snapshot', function () {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $this->postJson(route('refunds.cancel', $refund))->assertOk();
    expect($refund->fresh()->status)->toBe('cancelled')->and(CompanyErrorRefund::query()->count())->toBe(0);
    $log = AuditLog::query()->latest('id')->first();
    expect($log->event)->toBe('status_changed')
        ->and($log->old_values['company_error'][0]['loss_amount'])->toBe('200.00')
        ->and($log->new_values['company_error'])->toBe([]);
    $this->getJson(route('company-loss-reports.data'))->assertOk()->assertJsonPath('totals.total_loss', 0);
});

it('keeps loss records when cancellation or edits are prohibited', function (string $state) {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $refund->update($state === 'completed' ? ['status' => 'completed'] : ['adjustment_started_at' => now()]);
    $this->postJson(route('refunds.cancel', $refund))->assertUnprocessable();
    $this->putJson(route('refunds.update', $refund), $this->payload)->assertUnprocessable();
    expect(CompanyErrorRefund::query()->count())->toBe(1);
})->with(['completed', 'adjusted']);

it('rolls back both tables when audit persistence fails', function () {
    $this->mock(AuditLogService::class)->shouldReceive('snapshot')->andThrow(new RuntimeException('Audit unavailable'));
    expect(fn () => app(RefundService::class)->partialRefund($this->sale, $this->payload))->toThrow(RuntimeException::class);
    expect(Refund::query()->count())->toBe(0)
        ->and(CompanyErrorRefund::query()->count())->toBe(0)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('retains company loss throughout approvals and completion without counting gross refunds twice', function () {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $this->postJson(route('refunds.approve', $refund))->assertOk();
    $this->getJson(route('company-loss-reports.data'))->assertOk()
        ->assertJsonPath('totals.pending_loss', 200)->assertJsonPath('totals.completed_loss', 0);
    $this->postJson(route('refunds.payment-details', $refund), ['refund_payment_details' => 'Cash at office'])->assertOk();
    $this->postJson(route('refunds.customer-payment', $refund))->assertOk();
    expect($refund->fresh()->customerPayments()->sum('amount'))->toEqual(1000);
    $this->getJson(route('company-loss-reports.data'))->assertOk()
        ->assertJsonPath('totals.completed_loss', 200)->assertJsonPath('totals.pending_loss', 0);
    $this->getJson(route('refund-reports.data', ['company_error_only' => 1]))->assertOk()
        ->assertJsonPath('totals.refund_amount', '1000.00')
        ->assertJsonPath('totals.refund_outflow_amount', '1000.00')
        ->assertJsonPath('data.0.company_error.loss_amount', '200.00');
    $this->getJson(route('reports.data'))->assertOk()->assertJsonPath('totals.total_gross_ticket_refund_amount', '1,000.00')
        ->assertJsonPath('totals.report_refund_outflow', '1,000.00')
        ->assertJsonPath('data.0.net_cash', 0);
    $this->get(route('refunds.details', $this->sale))->assertOk()->assertSee('Company Error')->assertSee($this->payload['company_error_reason']);
});

it('filters the separate loss report and totals across all pages', function () {
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertOk();
    $refund = Refund::query()->sole();
    $refund->forceFill(['requested_at' => '2026-11-09 18:00:00'])->save();
    $other = $refund->replicate();
    $other->status = 'completed';
    $other->requested_at = '2026-11-10 12:00:00';
    $other->save();
    CompanyErrorRefund::factory()->for($other)->fullLoss()->create();
    $this->getJson(route('company-loss-reports.data', ['length' => 1]))->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('totals.total_loss', 1200)->assertJsonPath('recordsFiltered', 2);
    $this->getJson(route('company-loss-reports.data', ['loss_type' => 'custom', 'status' => 'completed']))->assertOk()
        ->assertJsonPath('totals.total_loss', 1000)->assertJsonPath('recordsFiltered', 1);
    $this->getJson(route('company-loss-reports.data', ['requested_from' => '2026-11-10', 'requested_to' => '2026-11-10']))->assertOk()
        ->assertJsonPath('recordsFiltered', 2);
    $this->getJson(route('company-loss-reports.data', ['search' => ['value' => 'Incorrect booking']]))->assertOk()
        ->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 1)->assertJsonPath('totals.total_loss', 200);
    $this->getJson(route('company-loss-reports.data', ['company_id' => $this->company->id, 'ship_id' => $this->ship->id]))->assertOk()->assertJsonPath('recordsFiltered', 2);
    $this->getJson(route('company-loss-reports.data', ['requested_from' => '2026-11-11', 'requested_to' => '2026-11-10']))->assertUnprocessable();
});

it('renders the modal and separate report and requires the corresponding permissions', function () {
    $this->get(route('refunds.create'))->assertOk()->assertSee('companyErrorInput')->assertSee('Partner Share Loss')->assertSee('companyLossPercentInput');
    $this->get(route('company-loss-reports.index'))->assertOk()->assertSee('Company Loss Report')->assertSee('Completed Company Loss')->assertSee('All companies');
    $viewer = User::factory()->create();
    $this->actingAs($viewer)->get(route('company-loss-reports.index'))->assertForbidden();
    $this->getJson(route('company-loss-reports.data'))->assertForbidden();
    $this->postJson(route('refunds.partial', $this->sale), $this->payload)->assertForbidden();
});

it('keeps cash collection balances consistent with a company-funded partner share', function () {
    app(RefundService::class)->partialRefund($this->sale, $this->payload);
    $refund = Refund::query()->sole();
    app(RefundService::class)->refundCustomer($refund, []);
    expect(app(\App\Services\Finance\CashCollectionService::class)->summary()['availableCashAmount'])->toBe(0.0);
});

it('rolls back cancellation and loss deletion when audit storage fails', function () {
    app(RefundService::class)->partialRefund($this->sale, $this->payload);
    $refund = Refund::query()->sole();
    $this->partialMock(AuditLogService::class)->shouldReceive('record')->andThrow(new RuntimeException('Audit unavailable'));

    expect(fn () => app(RefundService::class)->cancel($refund))->toThrow(RuntimeException::class);
    expect($refund->fresh()->status)->toBe('requested')
        ->and($refund->fresh()->companyErrorRefund->loss_amount)->toBe('200.00');
});

it('retains company error details in audit history when a refund is deleted', function () {
    app(RefundService::class)->partialRefund($this->sale, $this->payload);
    $refund = Refund::query()->sole();
    app(RefundService::class)->delete($refund);

    expect(Refund::query()->count())->toBe(0)->and(CompanyErrorRefund::query()->count())->toBe(0);
    $log = AuditLog::query()->latest('id')->first();
    expect($log->event)->toBe('deleted')
        ->and($log->old_values['company_error'][0]['reason'])->toBe($this->payload['company_error_reason'])
        ->and($log->sale_id)->toBe($this->sale->id);
});

it('provides an idempotent development seeder for the company loss workflow', function () {
    $this->seed(CompanyErrorRefundSeeder::class);
    $this->seed(CompanyErrorRefundSeeder::class);

    expect(CompanyErrorRefund::query()->count())->toBe(1)
        ->and(CompanyErrorRefund::query()->sole()->loss_amount)->toBe('200.00');
});
