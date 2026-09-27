<?php

use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\Shipment;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Models\VerifyTracker;
use App\Services\Sales\SaleGroupingService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\SteadfastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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
        'whatsapp' => '01712345678',
        'address' => '12 Road, Dhaka',
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

    $service = new SaleStatusWorkflowService(\Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));

    $result = $service->verify($firstSale->id, 'ticket-printed');

    expect($result['success'])->toBeTrue()
        ->and($firstSale->fresh()->status)->toBe('ticket-printed')
        ->and($secondSale->fresh()->status)->toBe('ticket-printed')
        ->and(VerifyTracker::where('name', 'ticket-printed')->count())->toBe(2);
});

it('syncs a printed group to its furthest existing status', function () {
    $parcelCreatedSale = workflowSale($this->ship, $this->company, ['status' => 'shipment_id_entered']);
    $issuedSale = workflowSale($this->ship, $this->company, ['status' => 'ticket-issued']);

    PrintedTicket::create([
        'sales_id' => $parcelCreatedSale->id,
        'filename' => 'parcel-created.pdf',
        'group_by_id' => $parcelCreatedSale->id,
    ]);

    PrintedTicket::create([
        'sales_id' => $issuedSale->id,
        'filename' => 'issued.pdf',
        'group_by_id' => $parcelCreatedSale->id,
    ]);

    $service = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));

    $service->verify($parcelCreatedSale->id, 'ticket-printed');

    expect($parcelCreatedSale->fresh()->status)->toBe('shipment_id_entered')
        ->and($issuedSale->fresh()->status)->toBe('shipment_id_entered')
        ->and(VerifyTracker::where('name', 'shipment_id_entered')->count())->toBe(1);
});

it('does not rank office collection or refunds as fulfillment progress', function () {
    $shippedSale = workflowSale($this->ship, $this->company, ['status' => 'shipped']);
    $issuedSale = workflowSale($this->ship, $this->company, ['status' => 'ticket-issued']);
    $collectedSale = workflowSale($this->ship, $this->company, ['status' => 'collect_from_office']);
    $refundedSale = workflowSale($this->ship, $this->company, ['status' => 'refunded']);

    foreach ([$shippedSale, $issuedSale, $collectedSale, $refundedSale] as $sale) {
        PrintedTicket::create([
            'sales_id' => $sale->id,
            'filename' => 'sale-'.$sale->id.'.pdf',
            'group_by_id' => $shippedSale->id,
        ]);
    }

    $service = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));
    $service->verify($shippedSale->id, 'ticket-printed');

    expect($shippedSale->fresh()->status)->toBe('shipped')
        ->and($issuedSale->fresh()->status)->toBe('shipped')
        ->and($collectedSale->fresh()->status)->toBe('collect_from_office')
        ->and($refundedSale->fresh()->status)->toBe('refunded');
});

it('attaches a newly grouped sale to the existing parcel after print confirmation', function () {
    $mainSale = workflowSale($this->ship, $this->company, ['status' => 'shipment_id_entered']);
    $newSale = workflowSale($this->ship, $this->company, ['status' => 'ticket-issued']);

    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'existing.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $newSale->id,
        'filename' => 'new.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    Shipment::create(['ticket_id' => $mainSale->id, 'shipment_id' => 'SHP-123']);

    $service = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));
    $service->verify($mainSale->id, 'ticket-printed');

    expect($mainSale->fresh()->status)->toBe('shipment_id_entered')
        ->and($newSale->fresh()->status)->toBe('shipment_id_entered')
        ->and(Shipment::where('ticket_id', $newSale->id)->value('shipment_id'))->toBe('SHP-123');
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
    $steadfast->shouldReceive('statusByInvoice')
        ->once()
        ->with('TICKET-'.$sale->id)
        ->andReturn(['http_status' => 404, 'body' => ['status' => 404]]);
    $steadfast->shouldReceive('bulkCreate')
        ->once()
        ->andReturn(['data' => []]);

    $service = new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class));

    $result = $service->verify($sale->id, 'shipment_id_entered');

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'Failed to create shipment',
        'status' => 500,
    ])
        ->and($sale->fresh()->status)->toBe('ticket-printed')
        ->and(Shipment::count())->toBe(0);
});

it('does not create a duplicate parcel when the invoice already exists remotely', function () {
    $sale = workflowSale($this->ship, $this->company, ['status' => 'ticket-printed']);
    PrintedTicket::create([
        'sales_id' => $sale->id,
        'filename' => 'shipment.pdf',
        'group_by_id' => $sale->id,
    ]);

    $steadfast = Mockery::mock(SteadfastService::class);
    $steadfast->shouldReceive('statusByInvoice')
        ->once()
        ->with('TICKET-'.$sale->id)
        ->andReturn(['http_status' => 200, 'body' => ['status' => 200, 'delivery_status' => 'pending']]);
    $steadfast->shouldNotReceive('bulkCreate');

    $service = new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class));
    $result = $service->verify($sale->id, 'shipment_id_entered');

    expect($result['success'])->toBeFalse()
        ->and($sale->fresh()->status)->toBe('ticket-printed')
        ->and(Shipment::count())->toBe(0);
});

it('stores the consignment id from a top-level bulk response list', function () {
    $sale = workflowSale($this->ship, $this->company, ['status' => 'ticket-printed']);
    PrintedTicket::create([
        'sales_id' => $sale->id,
        'filename' => 'shipment.pdf',
        'group_by_id' => $sale->id,
    ]);

    $steadfast = Mockery::mock(SteadfastService::class);
    $steadfast->shouldReceive('statusByInvoice')
        ->once()
        ->andReturn(['http_status' => 404, 'body' => ['status' => 404]]);
    $steadfast->shouldReceive('bulkCreate')
        ->once()
        ->andReturn([[
            'status' => 'success',
            'consignment_id' => 987654,
        ]]);

    $result = (new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class)))
        ->verify($sale->id, 'shipment_id_entered');

    expect($result['success'])->toBeTrue()
        ->and(Shipment::where('ticket_id', $sale->id)->value('shipment_id'))->toBe('987654')
        ->and($sale->fresh()->status)->toBe('shipment_id_entered');
});

it('does not save a consignment when the bulk response marks the item as failed', function () {
    $sale = workflowSale($this->ship, $this->company, ['status' => 'ticket-printed']);
    PrintedTicket::create([
        'sales_id' => $sale->id,
        'filename' => 'shipment.pdf',
        'group_by_id' => $sale->id,
    ]);

    $steadfast = Mockery::mock(SteadfastService::class);
    $steadfast->shouldReceive('statusByInvoice')
        ->once()
        ->andReturn(['http_status' => 404, 'body' => ['status' => 404]]);
    $steadfast->shouldReceive('bulkCreate')
        ->once()
        ->andReturn(['data' => [[
            'status' => 'error',
            'consignment_id' => null,
        ]]]);

    $result = (new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class)))
        ->verify($sale->id, 'shipment_id_entered');

    expect($result['success'])->toBeFalse()
        ->and(Shipment::count())->toBe(0)
        ->and($sale->fresh()->status)->toBe('ticket-printed');
});

it('blocks parcel creation when an existing group contains an office collection sale', function () {
    $courierSale = workflowSale($this->ship, $this->company, ['status' => 'ticket-printed']);
    $officeSale = workflowSale($this->ship, $this->company, [
        'status' => 'ticket-issued',
        'collect_from_office' => true,
    ]);
    PrintedTicket::create([
        'sales_id' => $courierSale->id,
        'filename' => 'courier.pdf',
        'group_by_id' => $courierSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $officeSale->id,
        'filename' => 'office.pdf',
        'group_by_id' => $courierSale->id,
    ]);

    $steadfast = Mockery::mock(SteadfastService::class);
    $steadfast->shouldNotReceive('statusByInvoice');
    $steadfast->shouldNotReceive('bulkCreate');

    $result = (new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class)))
        ->verify($courierSale->id, 'shipment_id_entered');

    expect($result)->toMatchArray([
        'success' => false,
        'status' => 422,
    ])
        ->and($result['message'])->toContain('Collect from Office')
        ->and(Shipment::count())->toBe(0)
        ->and($courierSale->fresh()->status)->toBe('ticket-printed')
        ->and($officeSale->fresh()->status)->toBe('ticket-issued');
});

it('marks every sale in an office collection group as collected', function () {
    $mainSale = workflowSale($this->ship, $this->company, [
        'status' => 'ticket-printed',
        'collect_from_office' => true,
    ]);
    $secondSale = workflowSale($this->ship, $this->company, [
        'status' => 'ticket-printed',
        'collect_from_office' => true,
    ]);
    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'office-main.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $secondSale->id,
        'filename' => 'office-second.pdf',
        'group_by_id' => $mainSale->id,
    ]);

    $result = (new SaleStatusWorkflowService(
        Mockery::mock(SteadfastService::class),
        app(SaleGroupingService::class),
    ))->verify($mainSale->id, 'collect_from_office');

    expect($result['success'])->toBeTrue()
        ->and($mainSale->fresh()->status)->toBe('collect_from_office')
        ->and($secondSale->fresh()->status)->toBe('collect_from_office')
        ->and(VerifyTracker::where('name', 'collect_from_office')->count())->toBe(2);
});

it('creates a courier parcel for same-whatsapp grouped sales with different addresses', function () {
    $mainSale = workflowSale($this->ship, $this->company, ['status' => 'ticket-printed']);
    $differentAddressSale = workflowSale($this->ship, $this->company, [
        'status' => 'ticket-issued',
        'address' => '44 Avenue, Dhaka',
    ]);
    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'courier-main.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $differentAddressSale->id,
        'filename' => 'courier-other.pdf',
        'group_by_id' => $mainSale->id,
    ]);

    $steadfast = Mockery::mock(SteadfastService::class);
    $steadfast->shouldReceive('statusByInvoice')
        ->once()
        ->with('TICKET-'.$mainSale->id)
        ->andReturn(['http_status' => 404, 'body' => ['status' => 404]]);
    $steadfast->shouldReceive('bulkCreate')
        ->once()
        ->andReturn([['status' => 'success', 'consignment_id' => 987654]]);

    $result = (new SaleStatusWorkflowService($steadfast, app(SaleGroupingService::class)))
        ->verify($mainSale->id, 'shipment_id_entered');

    expect($result['success'])->toBeTrue()
        ->and(Shipment::where('ticket_id', $mainSale->id)->value('shipment_id'))->toBe('987654')
        ->and($mainSale->fresh()->status)->toBe('shipment_id_entered')
        ->and($differentAddressSale->fresh()->status)->toBe('shipment_id_entered');
});

it('uses the provided consignment id in the status check URL', function () {
    config([
        'steadfast.api_key' => 'test-api-key',
        'steadfast.secret_key' => 'test-secret-key',
        'steadfast.base_url' => 'https://portal.packzy.com/api/v1',
    ]);
    Http::fake([
        'portal.packzy.com/api/v1/status_by_cid/456' => Http::response(['status' => 200]),
    ]);

    $response = app(SteadfastService::class)->statusCheck(456);

    expect($response)->toBe(['status' => 200]);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/status_by_cid/456'));
});

it('sends the bulk payload as JSON data with Steadfast authentication headers', function () {
    config([
        'steadfast.api_key' => 'test-api-key',
        'steadfast.secret_key' => 'test-secret-key',
        'steadfast.base_url' => 'https://portal.packzy.com/api/v1',
    ]);
    Http::fake([
        'portal.packzy.com/api/v1/create_order/bulk-order' => Http::response(['data' => []]),
    ]);

    $orders = [['invoice' => 'TICKET-456', 'recipient_name' => 'Test Customer']];
    app(SteadfastService::class)->bulkCreate($orders);

    Http::assertSent(function (Request $request) use ($orders): bool {
        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/create_order/bulk-order')
            && $request->hasHeader('Api-Key', 'test-api-key')
            && $request->hasHeader('Secret-Key', 'test-secret-key')
            && json_decode($request->data()['data'], true) === $orders;
    });
});

it('throws when Steadfast rejects the bulk request at HTTP level', function () {
    config([
        'steadfast.api_key' => 'test-api-key',
        'steadfast.secret_key' => 'test-secret-key',
        'steadfast.base_url' => 'https://portal.packzy.com/api/v1',
    ]);
    Http::fake([
        'portal.packzy.com/api/v1/create_order/bulk-order' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    expect(fn () => app(SteadfastService::class)->bulkCreate([['invoice' => 'TICKET-456']]))
        ->toThrow(RuntimeException::class, 'Steadfast bulk create failed with HTTP 401.');
});
