<?php

use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->seller = User::factory()->create(['name' => 'Seller Rahim']);

    $this->ship = Ship::create(['name' => 'MV Test Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Test Company', 'status' => 1]);

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Customer One',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'company_id' => $this->company->id,
        'journey_date' => '2026-10-01',
        'return_date' => '2026-10-05',
        'ticket_value' => 500,
        'other_fee' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'sold_by' => $this->seller->id,
        'ticket_count' => 1,
        'status' => 'pending',
    ]);
});

it('shows the sold by user name in a locked field and hides the customer id field', function () {
    $this->actingAs($this->admin)
        ->get(route('ship-ticket-sales.show', $this->sale))
        ->assertOk()
        ->assertSee('value="Seller Rahim" disabled', false)
        ->assertSee('<input type="hidden" name="sold_by" id="sold_by" value="'.$this->seller->id.'">', false)
        ->assertDontSee('Customer ID *');
});

it('keeps sold by unchanged when the sale is updated', function () {
    $otherSeller = User::factory()->create(['name' => 'Other Seller']);

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$this->sale->id, [
            'customer_name' => 'Customer Updated',
            'customer_mobile' => '01712345678',
            'whatsapp' => '01712345678',
            'email' => null,
            'nid' => null,
            'date_of_birth' => null,
            'address' => 'Dhaka',
            'collect_from_office' => 0,
            'ship_id' => $this->ship->id,
            'company_id' => $this->company->id,
            'journey_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'ticket_count' => 1,
            'ticket_value' => 500,
            'received_amount' => 500,
            'due_amount' => 0,
            'other_fee' => 0,
            'total_payable' => 500,
            'bftn_status' => null,
            'sales_source' => 'Direct',
            'sold_by' => (string) $otherSeller->id,
            'issued_date' => '2026-09-23',
            'status' => 'pending',
            'remark1' => null,
            'remark2' => null,
        ])
        ->assertSessionHasNoErrors();

    $sale = $this->sale->fresh();

    expect($sale->customer_name)->toBe('Customer Updated')
        ->and($sale->sold_by)->toBe($this->seller->id);
});

it('restricts pending action permissions to pending sales', function (string $action) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate("sales.pending.{$action}", 'web'));

    expect(Gate::forUser($user)->allows("sales.{$action}-record", $this->sale))->toBeTrue();

    $this->sale->update(['status' => 'payment-verified']);

    expect(Gate::forUser($user)->allows("sales.{$action}-record", $this->sale))->toBeFalse();

    $user->givePermissionTo(Permission::findOrCreate("sales.{$action}", 'web'));

    expect(Gate::forUser($user)->allows("sales.{$action}-record", $this->sale))->toBeTrue();
})->with(['edit', 'delete']);

it('blocks pending-only users from editing or deleting verified sales through direct requests', function () {
    $user = User::factory()->create();
    foreach (['sales.view', 'sales.pending.edit', 'sales.pending.delete'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $this->sale->update(['status' => 'payment-verified']);
    $url = '/ship-ticket-sales/'.$this->sale->id;

    $this->actingAs($user)->get($url)->assertForbidden();
    $this->actingAs($user)->putJson($url, [])->assertForbidden();
    $this->actingAs($user)->deleteJson($url)->assertForbidden();
    $this->actingAs($user)->deleteJson('/sale/delete/'.$this->sale->id)->assertForbidden();

    $this->sale->update(['status' => 'pending']);
    $this->actingAs($user)->get($url)->assertOk();
    $this->actingAs($user)->deleteJson($url)->assertOk();
    expect($this->sale->fresh()->trashed())->toBeTrue();
});

it('saves the edited co-passenger mobile number', function () {
    $this->sale->coPassengers()->create([
        'name' => 'Passenger One',
        'co_passernger_number' => '01711111111',
    ]);

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$this->sale->id, [
            'customer_name' => 'Customer One',
            'customer_mobile' => '01712345678',
            'whatsapp' => '01712345678',
            'email' => null,
            'nid' => null,
            'date_of_birth' => null,
            'collect_from_office' => 1,
            'ship_id' => $this->ship->id,
            'company_id' => $this->company->id,
            'journey_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'sales_source' => 'Direct',
            'issued_date' => '2026-09-23',
            'status' => 'pending',
            'remark1' => null,
            'remark2' => null,
            'co_passengers' => [[
                'name' => 'Passenger One',
                'co_passernger_number' => '01922222222',
            ]],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    expect($this->sale->coPassengers()->count())->toBe(1)
        ->and($this->sale->coPassengers()->first()->co_passernger_number)->toBe('01922222222');
});

it('recalculates payable from departure and return quantities across packages', function () {
    $departurePackage = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'Departure package',
        'price' => 200,
        'round_trip_price' => 390,
    ]);
    $returnPackage = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'Return package',
        'price' => 100,
        'round_trip_price' => 180,
    ]);

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$this->sale->id, [
            'customer_name' => 'Customer One',
            'customer_mobile' => '01712345678',
            'whatsapp' => '01712345678',
            'email' => null,
            'nid' => null,
            'date_of_birth' => null,
            'collect_from_office' => 1,
            'address' => null,
            'ship_id' => $this->ship->id,
            'company_id' => $this->company->id,
            'journey_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'other_fee' => 20,
            'discount_amount' => 10,
            'total_payable' => 0,
            'sales_source' => 'Direct',
            'sold_by' => (string) $this->seller->id,
            'issued_date' => '2026-09-23',
            'status' => 'pending',
            'departure_quantity' => [$departurePackage->id => 3],
            'return_quantity' => [$returnPackage->id => 2],
            'remark1' => null,
            'remark2' => null,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    $sale = $this->sale->fresh();

    expect($sale->ticket_count)->toBe(5)
        ->and((float) $sale->ticket_value)->toBe(760.0)
        ->and((float) $sale->total_payable)->toBe(770.0);
});

it('includes the increased return quantity without changing departure quantity', function () {
    $package = ShipPackage::create([
        'ship_id' => $this->ship->id,
        'name' => 'Round trip package',
        'price' => 200,
        'round_trip_price' => 390,
    ]);

    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$this->sale->id, [
            'customer_name' => 'Customer One',
            'customer_mobile' => '01712345678',
            'whatsapp' => '01712345678',
            'email' => null,
            'nid' => null,
            'date_of_birth' => null,
            'collect_from_office' => 1,
            'address' => null,
            'ship_id' => $this->ship->id,
            'company_id' => $this->company->id,
            'journey_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'other_fee' => 0,
            'discount_amount' => 0,
            'total_payable' => 0,
            'sales_source' => 'Direct',
            'sold_by' => (string) $this->seller->id,
            'issued_date' => '2026-09-23',
            'status' => 'pending',
            'departure_quantity' => [$package->id => 2],
            'return_quantity' => [$package->id => 3],
            'remark1' => null,
            'remark2' => null,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    $sale = $this->sale->fresh();

    expect($sale->ticket_count)->toBe(5)
        ->and((float) $sale->ticket_value)->toBe(970.0)
        ->and((float) $sale->total_payable)->toBe(970.0);
});

it('shows the original due and effective refund due adjustments separately on edit', function (float $adjustedAmount, bool $isEffective) {
    $package = ShipPackage::create(['ship_id' => $this->ship->id, 'name' => 'Due adjustment deck', 'price' => 500]);
    $this->sale->categories()->create(['package_id' => $package->id, 'quantity' => 1, 'unit_price' => 500, 'type' => 'departure']);
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 200, 'is_bftn' => false, 'settlement_status' => 'received']);
    $this->sale->refunds()->create([
        'refund_type' => 'partial',
        'status' => $isEffective ? 'completed' : 'partner_approved',
        'refunded_number_of_tickets' => 1,
        'refunded_amount' => 300,
        'due_adjusted_amount' => $adjustedAmount,
    ]);
    $effectiveAdjustment = $isEffective ? $adjustedAmount : 0.0;

    $response = $this->actingAs($this->admin)->get(route('ship-ticket-sales.show', $this->sale));
    $response->assertOk()
        ->assertViewHas('dueBeforeAdjustment', 300.0)
        ->assertViewHas('dueAdjustedAmount', $effectiveAdjustment)
        ->assertSee('Due Adjusted Amount');
    expect($response->getContent())->toMatch('/id="due_amount"\s+readonly value="300\.00"/')
        ->toMatch('/id="due_adjusted_amount" readonly\s+value="'.preg_quote(number_format($effectiveAdjustment, 2, '.', ''), '/').'"/')
        ->and((float) $this->sale->fresh()->due_amount)->toBe(300.0 - $effectiveAdjustment);
})->with([
    'no adjustment' => [0.0, true],
    'partial adjustment' => [100.0, true],
    'fully adjusted' => [300.0, true],
    'not yet effective' => [100.0, false],
]);

it('adds effective refund adjustments and keeps pending BFTN out of the original due', function () {
    $this->sale->payments()->create(['payment_method' => 'Bank', 'received_amount' => 100, 'is_bftn' => true, 'settlement_status' => 'pending']);
    foreach ([['adjustment_started_at' => now()], ['customer_refunded_at' => now()]] as $state) {
        $this->sale->refunds()->create(array_merge([
            'refund_type' => 'partial', 'status' => 'partner_approved',
            'refunded_number_of_tickets' => 1, 'refunded_amount' => 50, 'due_adjusted_amount' => 50,
        ], $state));
    }

    $this->actingAs($this->admin)->get(route('ship-ticket-sales.show', $this->sale))->assertOk()
        ->assertViewHas('dueBeforeAdjustment', 400.0)
        ->assertViewHas('dueAdjustedAmount', 100.0);
});

it('shows only filled remarks in red bold text at the top of the ticket issue page', function (?string $remark1, ?string $remark2) {
    $this->sale->update(['remark1' => $remark1, 'remark2' => $remark2]);
    $response = $this->actingAs($this->admin)->get(route('ship-ticket-issue.show', $this->sale));
    $response->assertOk()->assertDontSee('Collection method:')
        ->assertDontSee('name="remark1"', false)->assertDontSee('name="remark2"', false);
    $html = $response->getContent();
    foreach (['Remark 1: ' => $remark1, 'Remark 2: ' => $remark2] as $label => $value) {
        if (filled($value)) {
            $response->assertSee($label.$value);
            expect(substr_count($html, e($label.$value)))->toBe(1);
        } else {
            $response->assertDontSee($label);
        }
    }
    if (filled($remark1) || filled($remark2)) {
        $response->assertSee('id="ticketIssueRemarks"', false)
            ->assertSee('whitespace-pre-line font-bold text-red-600', false);
        expect(strpos($html, 'id="ticketIssueRemarks"'))->toBeLessThan(strpos($html, 'Customer Information'));
    } else {
        $response->assertDontSee('id="ticketIssueRemarks"', false);
    }
    expect($this->sale->fresh()->remark1)->toBe($remark1)
        ->and($this->sale->fresh()->remark2)->toBe($remark2);
})->with([
    'both remarks' => ['Collect early', 'Call before boarding'],
    'first only' => ['Collect early', null],
    'second only' => [null, 'Call before boarding'],
    'both null' => [null, null],
    'empty remarks' => ['', ''],
    'whitespace remarks' => ['   ', "\n\t"],
    'escaped and multiline text' => ["<script>alert('test')</script>\nNext line", 'Use <VIP> gate'],
]);
