<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Sales\SalesDataTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('returns the WhatsApp username for sales without a WhatsApp number', function () {
    $ship = Ship::create(['name' => 'Username Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Username Company', 'status' => 1]);
    $sale = salesDataTableSale($ship, $company, 'pending');
    $sale->update(['whatsapp' => null, 'whatsapp_username' => '@ticket_customer']);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 10]),
        'pending'
    )->getData(true);

    expect($response['data'][0]['whatsapp'])->toBeNull()
        ->and($response['data'][0]['whatsapp_username'])->toBe('@ticket_customer')
        ->and($response['data'][0]['whatsapp_display'])->toBe('@ticket_customer')
        ->and(array_key_exists('total_payable', $response['data'][0]))->toBeTrue();
});

it('sorts sales by creation date with stable pagination', function (?string $sort, bool $oldestFirst) {
    $ship = Ship::create(['name' => 'Sort Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Sort Company', 'status' => 1]);
    $newer = salesDataTableSale($ship, $company, 'pending');
    $newer->forceFill(['created_at' => '2026-10-20 12:00:00'])->save();
    $older = salesDataTableSale($ship, $company, 'pending');
    $older->forceFill(['created_at' => '2026-10-19 12:00:00'])->save();
    $tied = salesDataTableSale($ship, $company, 'pending');
    $tied->forceFill(['created_at' => '2026-10-20 12:00:00'])->save();
    $expected = $oldestFirst ? [$older->id, $newer->id, $tied->id] : [$tied->id, $newer->id, $older->id];

    foreach ($expected as $offset => $id) {
        $response = app(SalesDataTableService::class)->response(
            new Request(['sort' => $sort, 'start' => $offset, 'length' => 1]),
            'pending'
        )->getData(true);

        expect(array_column($response['data'], 'id'))->toBe([$id]);
    }
})->with([
    'default latest' => [null, false],
    'latest' => ['latest', false],
    'oldest' => ['oldest', true],
    'invalid falls back to latest' => ['invalid', false],
]);

it('includes the seller name in the sales table response', function () {
    $ship = Ship::create(['name' => 'Seller Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Seller Company', 'status' => 1]);
    $seller = User::factory()->create(['name' => 'Sales Representative']);
    $sale = salesDataTableSale($ship, $company, 'pending');
    $sale->update(['sold_by' => $seller->id]);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 10]),
        'pending'
    )->getData(true);

    expect($response['data'][0]['sold_by'])->toBe($seller->id)
        ->and($response['data'][0]['seller']['name'])->toBe('Sales Representative');
});

it('keeps sales table pagination bounded for invalid and oversized lengths', function () {
    $ship = Ship::create(['name' => 'Pagination Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Pagination Company', 'status' => 1]);

    foreach (range(1, 12) as $index) {
        $sale = salesDataTableSale($ship, $company, 'pending');
        $sale->update(['customer_name' => "Customer {$index}"]);
    }

    $service = app(SalesDataTableService::class);
    $negativeLengthResponse = $service->response(
        new Request(['start' => 0, 'length' => -1]),
        'pending'
    )->getData(true);
    $oversizedLengthResponse = $service->response(
        new Request(['start' => 0, 'length' => 1000]),
        'pending'
    )->getData(true);

    expect($negativeLengthResponse['data'])->toHaveCount(10)
        ->and($oversizedLengthResponse['data'])->toHaveCount(12);
});

it('shows every member of a group containing an issued sale in the issued list', function () {
    $ship = Ship::create(['name' => 'Group Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Group Company', 'status' => 1]);
    $referenceSale = salesDataTableSale($ship, $company, 'ticket-printed');
    $issuedSale = salesDataTableSale($ship, $company, 'ticket-issued');
    $unrelatedSale = salesDataTableSale($ship, $company, 'ticket-printed');

    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'printed.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $issuedSale->id,
        'filename' => 'issued.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $unrelatedSale->id,
        'filename' => 'unrelated.pdf',
        'group_by_id' => $unrelatedSale->id,
    ]);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 20]),
        'ticket-issued'
    )->getData(true);
    $rows = collect($response['data']);
    $referenceRow = $rows->firstWhere('id', $referenceSale->id);
    $issuedRow = $rows->firstWhere('id', $issuedSale->id);
    $referenceFiles = collect($referenceRow['grouped_tickets']);

    expect($rows->pluck('id')->all())
        ->toContain($referenceSale->id, $issuedSale->id)
        ->not->toContain($unrelatedSale->id)
        ->and($response['recordsTotal'])->toBe(2)
        ->and($referenceFiles->pluck('filename')->all())->toBe(['printed.pdf', 'issued.pdf'])
        ->and($referenceFiles->pluck('sale.status')->all())->toBe(['ticket-printed', 'ticket-issued'])
        ->and($issuedRow['grouped_tickets'])->toBeEmpty()
        ->and($issuedRow['printed_tickets'][0]['group_by_id'])->toBe($referenceSale->id);

    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
        Permission::findOrCreate('sales.status.ticket-issued', 'web'),
        Permission::findOrCreate('sales.status.ticket-printed', 'web'),
    ]);

    $page = $this->actingAs($user)
        ->get(route('sales.index', 'ticket-issued'))
        ->assertOk();

    expect(str_contains($page->getContent(), 'Issue Tickets'))->toBeTrue();

    $this->get(route('sales.index', 'ticket-printed'))
        ->assertOk();
});

it('shows all group sales in the ticket printed list', function () {
    $ship = Ship::create(['name' => 'Group Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Group Company', 'status' => 1]);
    $mainSale = salesDataTableSale($ship, $company, 'ticket-printed');
    $referenceSale = salesDataTableSale($ship, $company, 'ticket-issued');

    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'main.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'reference.pdf',
        'group_by_id' => $mainSale->id,
    ]);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 20]),
        'ticket-printed'
    )->getData(true);

    expect(collect($response['data'])->pluck('id')->all())
        ->toContain($mainSale->id, $referenceSale->id)
        ->and($response['recordsTotal'])->toBe(2);
});

it('shows all group sales in the parcel list', function () {
    $ship = Ship::create(['name' => 'Group Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Group Company', 'status' => 1]);
    $mainSale = salesDataTableSale($ship, $company, 'shipment_id_entered');
    $referenceSale = salesDataTableSale($ship, $company, 'ticket-issued');

    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'main.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'reference.pdf',
        'group_by_id' => $mainSale->id,
    ]);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 20]),
        'shipment_id_entered'
    )->getData(true);

    expect(collect($response['data'])->pluck('id')->all())
        ->toContain($mainSale->id, $referenceSale->id)
        ->and($response['recordsTotal'])->toBe(2);
});

it('shows every member of an office collection group in the collected list', function () {
    $ship = Ship::create(['name' => 'Group Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Group Company', 'status' => 1]);
    $mainSale = salesDataTableSale($ship, $company, 'collect_from_office');
    $referenceSale = salesDataTableSale($ship, $company, 'ticket-printed');
    $mainSale->update(['collect_from_office' => true]);
    $referenceSale->update(['collect_from_office' => true]);

    PrintedTicket::create([
        'sales_id' => $mainSale->id,
        'filename' => 'office-main.pdf',
        'group_by_id' => $mainSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'office-reference.pdf',
        'group_by_id' => $mainSale->id,
    ]);

    $response = app(SalesDataTableService::class)->response(
        new Request(['start' => 0, 'length' => 20]),
        'collect_from_office'
    )->getData(true);

    expect(collect($response['data'])->pluck('id')->all())
        ->toContain($mainSale->id, $referenceSale->id)
        ->and($response['recordsTotal'])->toBe(2);
});

function salesDataTableSale(Ship $ship, Company $company, string $status): ShipTicketSale
{
    $sale = ShipTicketSale::create([
        'customer_name' => 'Grouped Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_value' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'status' => $status,
    ]);

    $package = ShipPackage::create([
        'ship_id' => $ship->id,
        'name' => 'Sales table package',
        'price' => 500,
        'round_trip_price' => 800,
    ]);
    Category::create([
        'ticket_id' => $sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'departure',
        'unit_price' => 500,
    ]);

    return $sale;
}
