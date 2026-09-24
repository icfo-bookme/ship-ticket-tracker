<?php

use App\Models\Company;
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

it('preselects the last seven days on the report page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('id="startCreateDate" value="'.now()->subDays(6)->toDateString().'"', false)
        ->assertSee('id="endCreateDate" value="'.now()->toDateString().'"', false);
});

it('resolves the total elements after the page markup is rendered', function () {
    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('function totalElements()', false)
        ->assertDontSee('const totalElements = {', false);
});
