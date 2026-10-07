<?php

use App\Models\Bftn;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Reports\BftnReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function bftnReportSale(Ship $ship, Company $company, array $attributes = []): ShipTicketSale
{
    return ShipTicketSale::create(array_merge([
        'customer_name' => 'BFTN Customer',
        'customer_mobile' => '01700000000',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'ticket_fee' => 100,
        'total_payable' => 100,
        'received_amount' => 100,
        'due_amount' => 0,
        'issued_date' => now()->toDateString(),
        'journey_date' => now()->addDay()->toDateString(),
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
        'bftn_status' => 'yes',
    ], $attributes));
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
    Bftn::create([
        'sales_id' => $receivedSale->id,
        'bftn_date_time' => now()->subDay(),
        'received_status' => true,
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
        ->and($result['data'][0]['id'])->toBe($pendingSale->id)
        ->and($result['data'][0]['received_status'])->toBeFalse()
        ->and($result['totals']['total_amount'])->toBe('250.00')
        ->and($result['totals']['pending_amount'])->toBe('250.00');

    $received = app(BftnReportService::class)->dataTable(new Request(['status' => 'received']));

    expect($received['recordsFiltered'])->toBe(1)
        ->and($received['data'][0]['id'])->toBe($receivedSale->id)
        ->and($received['data'][0]['received_status'])->toBeTrue()
        ->and($received['totals']['received_amount'])->toBe('750.00');
});
