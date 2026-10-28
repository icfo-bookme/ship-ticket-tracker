<?php

use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\Shipment;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));
    $this->ship = Ship::create(['name' => 'Package Filter Ship', 'status' => 1]);
    $this->otherShip = Ship::create(['name' => 'Other Ship', 'status' => 1]);
    $this->package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Deck', 'price' => 500]);
    $this->otherPackage = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Cabin', 'price' => 800]);
    $this->foreignPackage = ShipPackage::create(['ship_id' => $this->otherShip->id, 'name' => 'Other Deck', 'price' => 500]);
});

function packageFilteredSale(ShipPackage $package, array $attributes = []): ShipTicketSale
{
    $sale = ShipTicketSale::create([
        'customer_name' => 'Package Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $package->ship_id, 'sales_source' => 'Direct', 'status' => 'pending',
        'journey_date' => '2026-11-01', 'issued_date' => '2026-10-28', ...$attributes,
    ]);
    $sale->categories()->create(['package_id' => $package->id, 'quantity' => 1, 'type' => 'departure', 'unit_price' => $package->price]);

    return $sale;
}

it('filters departure dates inclusively and supports open ended ranges', function () {
    $before = packageFilteredSale($this->package, ['journey_date' => '2026-11-30']);
    $first = packageFilteredSale($this->package, ['journey_date' => '2026-12-01']);
    $last = packageFilteredSale($this->package, ['journey_date' => '2026-12-30']);
    packageFilteredSale($this->package, ['journey_date' => '2026-12-31']);
    $this->actingAs($this->admin);

    $range = ['status' => 'pending', 'journey_date_from' => '2026-12-01', 'journey_date_to' => '2026-12-30'];
    $response = $this->getJson(route('sales.data', $range))->assertOk()->assertJsonPath('recordsFiltered', 2);
    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())->toBe([$first->id, $last->id]);

    $this->getJson(route('sales.data', ['status' => 'pending', 'journey_date_from' => '2026-12-01']))
        ->assertOk()->assertJsonPath('recordsFiltered', 3);
    $this->getJson(route('sales.data', ['status' => 'pending', 'journey_date_to' => '2026-11-30']))
        ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $before->id);
    $this->getJson(route('sales.data', ['status' => 'pending', 'journey_date_from' => '2026-12-30', 'journey_date_to' => '2026-12-01']))
        ->assertUnprocessable()->assertJsonValidationErrors('journey_date_to');
    $this->postJson(route('sales.select-shipped'), ['journey_date_from' => '2026-12-30', 'journey_date_to' => '2026-12-01'])
        ->assertUnprocessable()->assertJsonValidationErrors('journey_date_to');
});

it('filters by package without duplicating sales with matching departure and return categories', function () {
    $sale = packageFilteredSale($this->package);
    $sale->categories()->create(['package_id' => $this->package->id, 'quantity' => 1, 'type' => 'return', 'unit_price' => 500]);
    packageFilteredSale($this->otherPackage);
    packageFilteredSale($this->foreignPackage);

    $this->actingAs($this->admin)->getJson(route('sales.data', [
        'status' => 'pending', 'ship_id' => $this->ship->id, 'package_id' => $this->package->id,
    ]))->assertOk()->assertJsonPath('recordsTotal', 3)->assertJsonPath('recordsFiltered', 1)
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $sale->id);
});

it('keeps company date sorting and pagination working alongside package selection', function () {
    $company = Company::create(['name' => 'Filter Company', 'status' => 1]);
    $old = packageFilteredSale($this->package, ['company_id' => $company->id]);
    $old->forceFill(['created_at' => '2026-10-27 00:00:00'])->save();
    $new = packageFilteredSale($this->package, ['company_id' => $company->id]);
    $new->forceFill(['created_at' => '2026-10-28 00:00:00'])->save();
    packageFilteredSale($this->package, ['journey_date' => '2026-11-02']);
    packageFilteredSale($this->otherPackage, ['company_id' => $company->id]);
    $filters = ['status' => 'pending', 'ship_id' => $this->ship->id, 'package_id' => $this->package->id,
        'company_id' => $company->id, 'journey_date' => '2026-11-01', 'length' => 1];

    $this->actingAs($this->admin)->getJson(route('sales.data', $filters))->assertOk()
        ->assertJsonPath('recordsFiltered', 2)->assertJsonPath('data.0.id', $new->id);
    $this->getJson(route('sales.data', [...$filters, 'start' => 1]))->assertOk()->assertJsonPath('data.0.id', $old->id);
    $this->getJson(route('sales.data', [...$filters, 'sort' => 'oldest']))->assertOk()->assertJsonPath('data.0.id', $old->id);
});

it('rejects a package from a different ship and packages without a ship', function () {
    $this->actingAs($this->admin)->getJson(route('sales.data', [
        'status' => 'pending', 'ship_id' => $this->ship->id, 'package_id' => $this->foreignPackage->id,
    ]))->assertUnprocessable()->assertJsonValidationErrors('package_id');
    $this->getJson(route('sales.data', ['status' => 'pending', 'package_id' => $this->package->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('ship_id');
});

it('supports all packages and a valid package with no matching sales', function () {
    packageFilteredSale($this->package);
    packageFilteredSale($this->otherPackage);
    $this->actingAs($this->admin)->getJson(route('sales.data', ['status' => 'pending', 'ship_id' => $this->ship->id]))
        ->assertOk()->assertJsonPath('recordsFiltered', 2);
    $this->getJson(route('sales.data', ['status' => 'pending', 'ship_id' => $this->otherShip->id, 'package_id' => $this->foreignPackage->id]))
        ->assertOk()->assertJsonCount(0, 'data');
});

it('uses the same package filter for selecting all eligible parcel sales', function () {
    $selected = packageFilteredSale($this->package, ['status' => 'shipment_id_entered']);
    $other = packageFilteredSale($this->otherPackage, ['status' => 'shipment_id_entered']);
    foreach ([$selected, $other] as $sale) {
        PrintedTicket::create(['sales_id' => $sale->id, 'filename' => $sale->id.'.pdf', 'group_by_id' => $sale->id]);
        Shipment::create(['ticket_id' => $sale->id, 'shipment_id' => 'SHIP-'.$sale->id]);
    }
    $this->actingAs($this->admin)->postJson(route('sales.select-shipped'), ['ship_id' => $this->ship->id, 'package_id' => $this->package->id])
        ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('sale_ids', [$selected->id]);
    $this->postJson(route('sales.select-shipped'), ['ship_id' => $this->ship->id, 'package_id' => $this->foreignPackage->id])
        ->assertUnprocessable()->assertJsonValidationErrors('package_id');
});

it('renders the dependent dropdown disabled and requires permission to read filtered sales', function () {
    $this->actingAs($this->admin)->get(route('sales.index', 'pending'))->assertOk()
        ->assertSee('id="packageFilter" disabled', false)->assertSee('Select a ship first');
    $this->actingAs(User::factory()->create())->getJson(route('sales.data', ['status' => 'pending', 'ship_id' => $this->ship->id]))
        ->assertForbidden();
});
