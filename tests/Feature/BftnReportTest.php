<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Reports\BftnReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Sales\SaleStatusWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('stores BFTN settlement and notification state on payments only', function () {
    expect(Schema::hasTable('bftn'))->toBeFalse()
        ->and(Schema::hasColumn('ship_ticket_sales', 'received_amount'))->toBeFalse()
        ->and(Schema::hasColumn('ship_ticket_sales', 'bftn_status'))->toBeFalse()
        ->and(Schema::hasColumn('ship_ticket_sales', 'number_of_ticket'))->toBeFalse()
        ->and(Schema::hasColumn('payments', 'bftn_notification_read_at'))->toBeTrue();
});

function bftnReportSale(Ship $ship, Company $company, array $attributes = []): ShipTicketSale
{
    $receivedAmount = (float) ($attributes['received_amount'] ?? 100);
    $isBftn = ($attributes['bftn_status'] ?? 'yes') === 'yes';
    unset($attributes['received_amount'], $attributes['bftn_status']);

    $attributes = array_merge([
        'customer_name' => 'BFTN Customer',
        'customer_mobile' => '01700000000',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'ticket_value' => 100,
        'total_payable' => 100,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'journey_date' => now()->addDay()->toDateString(),
        'ticket_count' => 1,
        'status' => 'ticket-issued',
    ], $attributes);
    $sale = ShipTicketSale::create($attributes);

    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => $isBftn ? 'Bank Transfer' : 'Cash',
        'is_bftn' => $isBftn,
        'settlement_status' => $isBftn ? 'pending' : 'received',
        'received_amount' => $receivedAmount,
        'bftn_issue_datetime' => $isBftn ? now()->subDay() : null,
    ]);

    return $sale;
}

it('shows a separate BFTN report to report viewers', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));

    $response = $this->actingAs($user)
        ->get(route('bftn-reports.index'))
        ->assertOk()
        ->assertSee('BFTN Report')
        ->assertSee('bftnReportTable')
        ->assertSee('bftnReportTable-page-loader');

    expect(strpos($response->getContent(), 'id="bftnReportTable"'))
        ->toBeLessThan(strpos($response->getContent(), 'id="bftnReportTotalCount"'));

    $this->actingAs($user)
        ->getJson(route('bftn-reports.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 0)
        ->assertJsonPath('totals.total_amount', '0.00');
});

it('reports and filters only BFTN sales with pending and received amounts', function () {
    $ship = Ship::create(['name' => 'River Ship']);
    $company = Company::create(['name' => 'BFTN Company']);
    $pendingSale = bftnReportSale($ship, $company, ['received_amount' => 250]);
    $receivedSale = bftnReportSale($ship, $company, ['received_amount' => 750]);
    $receivedPayment = Payment::where('sales_id', $receivedSale->id)->firstOrFail();
    $receivedPayment->update([
        'settlement_status' => 'received',
        'received_at' => now(),
    ]);
    bftnReportSale($ship, $company, ['bftn_status' => 'no', 'received_amount' => 500]);

    $result = app(BftnReportService::class)->dataTable(new Request([
        'status' => 'pending',
        'ship_id' => $ship->id,
        'start' => 0,
        'length' => 10,
    ]));

    expect($result['recordsTotal'])->toBe(1)
        ->and($result['recordsFiltered'])->toBe(1)
        ->and($result['data'][0]['sale_id'])->toBe($pendingSale->id)
        ->and($result['data'][0]['received_status'])->toBeFalse()
        ->and($result['totals']['total_amount'])->toBe('250.00')
        ->and($result['totals']['pending_amount'])->toBe('250.00');

    $received = app(BftnReportService::class)->dataTable(new Request(['status' => 'received']));

    expect($received['recordsFiltered'])->toBe(1)
        ->and($received['data'][0]['sale_id'])->toBe($receivedSale->id)
        ->and($received['data'][0]['received_status'])->toBeTrue()
        ->and($received['totals']['received_amount'])->toBe('750.00');
});

it('counts only settled payments as received and cash when a sale has mixed payments', function () {
    $ship = Ship::create(['name' => 'Mixed Payment Ship']);
    $company = Company::create(['name' => 'Mixed Payment Company']);
    $sale = bftnReportSale($ship, $company, [
        'received_amount' => 50,
        'due_amount' => 50,
        'bftn_status' => 'no',
    ]);
    $sale->payments()->delete();
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Cash',
        'received_amount' => 50,
    ]);
    Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'received_amount' => 25,
    ]);
    $bftnPayment = Payment::create([
        'sales_id' => $sale->id,
        'payment_method' => 'Bank Transfer',
        'is_bftn' => true,
        'settlement_status' => 'pending',
        'received_amount' => 25,
    ]);

    $report = app(SalesReportService::class)->dataTable(new Request(['start' => 0, 'length' => 10]));

    expect($report['totals']['total_received_amount'])->toBe('75.00')
        ->and($report['totals']['total_bftn_pending_amount'])->toBe('25.00')
        ->and($report['data'][0]['net_cash'])->toBe(75.0)
        ->and(app(BftnReportService::class)->dataTable(new Request(['status' => 'all']))['recordsTotal'])->toBe(1);

    app(SaleStatusWorkflowService::class)->markPaymentBftnReceived($bftnPayment->id, now()->toDateTimeString());

    $report = app(SalesReportService::class)->dataTable(new Request(['start' => 0, 'length' => 10]));

    expect($report['totals']['total_received_amount'])->toBe('100.00')
        ->and($report['totals']['total_bftn_pending_amount'])->toBe('0.00')
        ->and($report['totals']['total_bftn_received_amount'])->toBe('25.00')
        ->and((float) $sale->payments()->where(fn ($query) => $query->where('is_bftn', false)->orWhere('settlement_status', 'received'))->sum('received_amount'))->toBe(100.0)
        ->and((float) $sale->fresh()->due_amount)->toBe(0.0);
});
