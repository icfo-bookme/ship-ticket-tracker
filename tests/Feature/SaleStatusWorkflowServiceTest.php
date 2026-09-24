<?php

use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\Shipment;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\VerifyTracker;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\SteadfastService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->ship = Ship::create(['name' => 'MV Workflow Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Workflow Company', 'status' => 1]);
});

function workflowSale(Ship $ship, Company $company, array $overrides = []): ShipTicketSale
{
    return ShipTicketSale::create(array_merge([
        'customer_name' => 'Workflow Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
    ], $overrides));
}

it('marks every sale in a printed ticket group as ticket printed', function () {
    $firstSale = workflowSale($this->ship, $this->company);
    $secondSale = workflowSale($this->ship, $this->company);

    PrintedTicket::create([
        'sales_id' => $firstSale->id,
        'filename' => 'first.pdf',
        'group_by_id' => $firstSale->id,
    ]);

    PrintedTicket::create([
        'sales_id' => $secondSale->id,
        'filename' => 'second.pdf',
        'group_by_id' => $firstSale->id,
    ]);

    $service = new SaleStatusWorkflowService(\Mockery::mock(SteadfastService::class));

    $result = $service->verify($firstSale->id, 'ticket-printed');

    expect($result['success'])->toBeTrue()
        ->and($firstSale->fresh()->status)->toBe('ticket-printed')
        ->and($secondSale->fresh()->status)->toBe('ticket-printed')
        ->and(VerifyTracker::where('name', 'ticket-printed')->count())->toBe(2);
});

it('returns a failure when steadfast does not provide a consignment id', function () {
    $sale = workflowSale($this->ship, $this->company, [
        'status' => 'ticket-printed',
        'address' => 'Dhaka',
        'due_amount' => 100,
    ]);

    PrintedTicket::create([
        'sales_id' => $sale->id,
        'filename' => 'shipment.pdf',
        'group_by_id' => $sale->id,
    ]);

    $steadfast = \Mockery::mock(SteadfastService::class);
    $steadfast->shouldReceive('bulkCreate')
        ->once()
        ->andReturn(['data' => []]);

    $service = new SaleStatusWorkflowService($steadfast);

    $result = $service->verify($sale->id, 'shipment_id_entered');

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'Failed to create shipment',
        'status' => 500,
    ])
        ->and($sale->fresh()->status)->toBe('ticket-printed')
        ->and(Shipment::count())->toBe(0);
});
