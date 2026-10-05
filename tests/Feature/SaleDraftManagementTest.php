<?php

use App\Models\SaleDraft;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $createSales = Permission::findOrCreate('sales.create', 'web');
    $role = Role::findOrCreate('Draft Manager', 'web');
    $role->givePermissionTo($createSales);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);

    $this->ship = Ship::create(['name' => 'Draft Ship', 'status' => 1]);
    $this->otherShip = Ship::create(['name' => 'Other Draft Ship', 'status' => 1]);
    $this->package = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'VIP Cabin',
        'price' => 100,
        'round_trip_price' => 180,
    ]);
    $this->otherPackage = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'Deck Seat',
        'price' => 50,
        'round_trip_price' => 90,
    ]);
    $this->foreignPackage = ShipPackage::create([
        'ship_id' => $this->otherShip->id,
        'name' => 'Other Ship Cabin',
        'price' => 75,
        'round_trip_price' => 130,
    ]);
});

function draftPayload(Ship $ship, ShipPackage $package, array $overrides = []): array
{
    return array_merge([
        'departure_date' => '2026-11-10',
        'return_date' => '2026-11-12',
        'ship_id' => $ship->id,
        'ticket_categories' => [
            'departure' => [
                ['package_id' => $package->id, 'name' => 'VIP Cabin', 'quantity' => 2],
            ],
            'return' => [
                ['package_id' => $package->id, 'name' => 'VIP Cabin', 'quantity' => 1],
            ],
        ],
        'details' => 'Two passengers, cabin requested.',
        'note' => 'Call before confirming.',
    ], $overrides);
}

it('stores ship and departure and return ticket category quantities on a draft', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/sale-drafts', draftPayload($this->ship, $this->package))
        ->assertCreated()
        ->assertJsonPath('ship_id', $this->ship->id)
        ->assertJsonCount(2, 'categories')
        ->assertJsonPath('categories.0.category_name', 'VIP Cabin')
        ->assertJsonPath('categories.0.journey_type', 'departure')
        ->assertJsonPath('categories.0.quantity', 2)
        ->assertJsonPath('categories.1.journey_type', 'return')
        ->assertJsonPath('categories.1.quantity', 1);

    expect(SaleDraft::find($response->json('id'))->ship_id)->toBe($this->ship->id);
});

it('filters drafts by selected ship and category', function () {
    $this->actingAs($this->user)->postJson('/sale-drafts', draftPayload($this->ship, $this->package))->assertCreated();
    $this->actingAs($this->user)->postJson('/sale-drafts', draftPayload($this->ship, $this->otherPackage, [
        'details' => 'Deck seat request.',
        'ticket_categories' => [
            'departure' => [
                ['package_id' => $this->otherPackage->id, 'name' => 'Deck Seat', 'quantity' => 1],
            ],
            'return' => [],
        ],
    ]))->assertCreated();

    $this->actingAs($this->user)
        ->getJson('/sale-drafts?draw=1&start=0&length=10&ship_id='.$this->ship->id.'&category_id='.$this->package->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.details', 'Two passengers, cabin requested.');
});

it('replaces saved category selections when a draft is updated', function () {
    $draft = $this->actingAs($this->user)
        ->postJson('/sale-drafts', draftPayload($this->ship, $this->package))
        ->assertCreated()
        ->json();

    $this->actingAs($this->user)
        ->putJson('/sale-drafts/'.$draft['id'], draftPayload($this->ship, $this->otherPackage, [
            'ticket_categories' => [
                'departure' => [
                    ['package_id' => $this->otherPackage->id, 'name' => 'Deck Seat', 'quantity' => 3],
                ],
                'return' => [],
            ],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.category_name', 'Deck Seat')
        ->assertJsonPath('data.categories.0.journey_type', 'departure')
        ->assertJsonPath('data.categories.0.quantity', 3);
});

it('returns draft dates in the edit record response', function () {
    $draft = $this->actingAs($this->user)
        ->postJson('/sale-drafts', draftPayload($this->ship, $this->package))
        ->assertCreated()
        ->json();

    $this->actingAs($this->user)
        ->getJson('/sale-drafts/'.$draft['id'])
        ->assertOk()
        ->assertJsonPath('departure_date', '2026-11-10T00:00:00.000000Z')
        ->assertJsonPath('return_date', '2026-11-12T00:00:00.000000Z');
});

it('rejects draft categories that do not belong to the selected ship', function () {
    $this->actingAs($this->user)
        ->postJson('/sale-drafts', draftPayload($this->ship, $this->package, [
            'ticket_categories' => [
                'departure' => [
                    ['package_id' => $this->foreignPackage->id, 'name' => 'Other Ship Cabin', 'quantity' => 1],
                ],
                'return' => [],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ticket_categories.departure.0.package_id');
});

it('requires a return date when a return category quantity is selected', function () {
    $this->actingAs($this->user)
        ->postJson('/sale-drafts', draftPayload($this->ship, $this->package, [
            'return_date' => null,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ticket_categories.return');
});

it('loads ship-dependent category filter controls on the manage page', function () {
    $this->actingAs($this->user)
        ->get('/sale-drafts/manage')
        ->assertOk()
        ->assertSee('id="draftShipFilter"', false)
        ->assertSee('id="draftCategoryFilter"', false)
        ->assertSee('id="draftDepartureCategories"', false)
        ->assertSee('id="draftReturnCategories"', false);
});
