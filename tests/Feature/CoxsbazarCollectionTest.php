<?php

use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\SalesSource;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Sales\SaleGroupingService;
use App\Services\Sales\SalesDataTableService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\SteadfastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));
    $this->actingAs($this->admin);
    $this->ship = Ship::create(['name' => 'Collection Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Collection Company', 'status' => 1]);
    $source = SalesSource::create(['name' => 'Direct', 'is_active' => true]);
    $package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Deck', 'price' => 500]);
    $this->payload = [
        'customer_name' => 'Collection Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'email' => null,
        'nid' => null,
        'date_of_birth' => null,
        'sales_source_id' => $source->id,
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'company_id' => $this->company->id,
        'collect_from_office' => 0,
        'collect_from_coxsbazar' => 1,
        'address' => null,
        'journey_date' => now()->addDay()->toDateString(),
        'return_date' => null,
        'issued_date' => now()->toDateString(),
        'sold_by' => (string) $this->admin->id,
        'status' => 'pending',
        'total_payable' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'remark1' => null,
        'remark2' => null,
        'ticket_categories' => ['departure' => [['package_id' => $package->id, 'quantity' => 1]]],
        'payment_methods' => [['method' => 'Cash', 'amount' => 500]],
    ];
});

it('creates bookings for either office without an address', function (int|bool $office, int|bool $coxsbazar) {
    $this->post('/ship-ticket-sales', array_replace($this->payload, [
        'collect_from_office' => $office,
        'collect_from_coxsbazar' => $coxsbazar,
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $sale = ShipTicketSale::query()->sole();
    expect($sale->collect_from_office)->toBe((bool) $office)
        ->and($sale->collect_from_coxsbazar)->toBe((bool) $coxsbazar)
        ->and($sale->isOfficeCollection())->toBeTrue();
})->with([[1, 0], [0, 1], [true, false], [false, true]]);

it('rejects conflicting offices and missing courier addresses on create and edit', function (string $action, array $options, string $error) {
    $payload = array_replace($this->payload, $options);
    if ($action === 'create') {
        $response = $this->post('/ship-ticket-sales', $payload);
    } else {
        $sale = ShipTicketSale::create($this->payload);
        $response = $this->put('/ship-ticket-sales/'.$sale->id, $payload);
    }
    $response->assertSessionHasErrors($error);
})->with(['create', 'edit'])->with([
    'both offices' => [['collect_from_office' => 1, 'collect_from_coxsbazar' => 1], 'collect_from_coxsbazar'],
    'no address' => [['collect_from_office' => 0, 'collect_from_coxsbazar' => 0], 'address'],
    'invalid flag' => [['collect_from_coxsbazar' => 'invalid'], 'collect_from_coxsbazar'],
]);

it('updates the collection office and can switch back to courier delivery', function () {
    $sale = ShipTicketSale::create(array_replace($this->payload, ['collect_from_office' => 1, 'collect_from_coxsbazar' => 0]));
    foreach ([[0, 1, null], [1, 0, null], [0, 0, 'Dhaka']] as [$office, $coxsbazar, $address]) {
        $this->put('/ship-ticket-sales/'.$sale->id, array_replace($this->payload, [
            'collect_from_office' => $office,
            'collect_from_coxsbazar' => $coxsbazar,
            'address' => $address,
        ]))->assertSessionHasNoErrors()->assertRedirect();
        expect($sale->fresh()->collect_from_office)->toBe((bool) $office)
            ->and($sale->fresh()->collect_from_coxsbazar)->toBe((bool) $coxsbazar);
    }
});

it('defaults legacy bookings to no Coxsbazar collection and preserves office behaviour', function () {
    $payload = $this->payload;
    unset($payload['collect_from_coxsbazar']);
    $payload['collect_from_office'] = 1;
    $this->post('/ship-ticket-sales', $payload)->assertSessionHasNoErrors();
    $sale = ShipTicketSale::query()->sole();
    expect($sale->collect_from_coxsbazar)->toBeFalse()->and($sale->isOfficeCollection())->toBeTrue();
    $this->put('/ship-ticket-sales/'.$sale->id, $payload)->assertSessionHasNoErrors();
    expect($sale->fresh()->collect_from_coxsbazar)->toBeFalse();
});

it('shows both collection options on create and the saved choice on edit', function () {
    $sale = ShipTicketSale::create($this->payload);
    $this->get('/ship-ticket-sales/create')->assertOk()
        ->assertSee('id="collect_from_office"', false)
        ->assertSee('id="collect_from_coxsbazar"', false);
    $this->get('/ship-ticket-sales/'.$sale->id)->assertOk()
        ->assertSee('id="collect_from_coxsbazar"', false)
        ->assertSee('Collect from Cox', false);
    $this->get('/ship-ticket-issue/'.$sale->id)->assertOk()->assertSee('Collection method:')->assertSee('Collect from Cox');
});

it('only groups sales using the same collection office', function () {
    $sale = ShipTicketSale::create($this->payload);
    PrintedTicket::create(['sales_id' => $sale->id, 'filename' => 'collection.pdf', 'group_by_id' => $sale->id]);
    $other = ShipTicketSale::create($this->payload);
    $grouping = app(SaleGroupingService::class);
    expect($grouping->canJoinGroup($other, $sale->id))->toBeTrue();
    $other->update(['collect_from_office' => true, 'collect_from_coxsbazar' => false]);
    expect($grouping->canJoinGroup($other, $sale->id))->toBeFalse()
        ->and($grouping->groupFailureMessage($other, $sale->id))->toContain('different offices');
    $other->update(['collect_from_office' => false, 'address' => 'Dhaka']);
    expect($grouping->canJoinGroup($other, $sale->id))->toBeFalse();
});

it('collects a printed Coxsbazar group and includes it in the existing collection list', function () {
    $sale = ShipTicketSale::create(array_replace($this->payload, ['status' => 'ticket-printed']));
    $other = ShipTicketSale::create(array_replace($this->payload, ['status' => 'ticket-printed']));
    foreach ([$sale, $other] as $member) {
        $member->categories()->create(['package_id' => $this->payload['ticket_categories']['departure'][0]['package_id'], 'quantity' => 1, 'type' => 'departure', 'unit_price' => 500]);
        PrintedTicket::create(['sales_id' => $member->id, 'filename' => $member->id.'.pdf', 'group_by_id' => $sale->id]);
    }
    $workflow = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));
    expect($workflow->verify($sale->id, 'collect_from_office')['success'])->toBeTrue()
        ->and($other->fresh()->status)->toBe('collect_from_office');
    $data = app(SalesDataTableService::class)->response(new Request, 'collect_from_office')->getData(true);
    expect($data['recordsTotal'])->toBe(2)
        ->and($data['data'][0]['collect_from_coxsbazar'])->toBeTrue();
});

it('blocks courier parcels for Coxsbazar collection and blocks collecting mixed office groups', function () {
    $sale = ShipTicketSale::create(array_replace($this->payload, ['status' => 'ticket-printed']));
    $other = ShipTicketSale::create(array_replace($this->payload, ['status' => 'ticket-printed', 'collect_from_office' => 1, 'collect_from_coxsbazar' => 0]));
    foreach ([$sale, $other] as $member) {
        $member->categories()->create(['package_id' => $this->payload['ticket_categories']['departure'][0]['package_id'], 'quantity' => 1, 'type' => 'departure', 'unit_price' => 500]);
        PrintedTicket::create(['sales_id' => $member->id, 'filename' => $member->id.'.pdf', 'group_by_id' => $sale->id]);
    }
    $workflow = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));
    expect($workflow->verify($sale->id, 'shipment_id_entered')['success'])->toBeFalse()
        ->and($workflow->verify($sale->id, 'collect_from_office')['success'])->toBeFalse()
        ->and($sale->fresh()->status)->toBe('ticket-printed');
});

it('adds the new column with zero for existing office and courier records', function () {
    $migration = require database_path('migrations/2026_11_21_110317_add_collect_from_coxsbazar_to_ship_ticket_sales_table.php');
    $migration->down();
    $payload = $this->payload;
    unset($payload['collect_from_coxsbazar']);
    $office = ShipTicketSale::create(array_replace($payload, ['collect_from_office' => 1]));
    $courier = ShipTicketSale::create(array_replace($payload, ['collect_from_office' => 0, 'address' => 'Dhaka']));
    $migration->up();
    expect($office->fresh()->collect_from_coxsbazar)->toBeFalse()
        ->and($office->fresh()->collect_from_office)->toBeTrue()
        ->and($courier->fresh()->collect_from_coxsbazar)->toBeFalse()
        ->and($courier->fresh()->address)->toBe('Dhaka');
});
