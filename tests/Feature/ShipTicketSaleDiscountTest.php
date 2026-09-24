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

    $this->ship = Ship::create(['name' => 'MV Discount Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Discount Company', 'status' => 1]);

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Discount Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'company_id' => $this->company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 20,
        'discount_amount' => 0,
        'total_payable' => 520,
        'received_amount' => 400,
        'due_amount' => 120,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);
});

function discountSalePayload(Ship $ship, Company $company, User $user, array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'New Discount Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'nid' => null,
        'email' => null,
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'address' => 'Dhaka',
        'journey_date' => '2026-10-01',
        'date_of_birth' => null,
        'return_date' => null,
        'ticket_fee' => 500,
        'other_fee' => 20,
        'discount_amount' => 0,
        'total_payable' => 520,
        'received_amount' => 400,
        'due_amount' => 120,
        'number_of_ticket' => 1,
        'ticket_category' => null,
        'bftn_status' => null,
        'company_id' => $company->id,
        'issued_date' => '2026-09-23',
        'status' => 'pending',
        'sold_by' => (string) $user->id,
        'remark1' => null,
        'remark2' => null,
    ], $overrides);
}

it('stores the discount amount and the computed totals when a sale is created', function () {
    $this->actingAs($this->admin)
        ->post('/ship-ticket-sales', discountSalePayload($this->ship, $this->company, $this->admin, [
            'discount_amount' => 50,
            'total_payable' => 470,
            'due_amount' => 70,
        ]))
        ->assertRedirect(route('ship-ticket-sales.create'));

    $sale = ShipTicketSale::latest('id')->first();

    expect((float) $sale->discount_amount)->toBe(50.0)
        ->and((float) $sale->ticket_fee)->toBe(500.0)
        ->and((float) $sale->other_fee)->toBe(20.0)
        ->and((float) $sale->total_payable)->toBe(470.0)
        ->and((float) $sale->due_amount)->toBe(70.0);
});

it('keeps the discount amount when the sale is updated', function () {
    $this->actingAs($this->admin)
        ->put('/ship-ticket-sales/'.$this->sale->id, discountSalePayload($this->ship, $this->company, $this->admin, [
            'discount_amount' => 45.5,
            'total_payable' => 474.5,
            'due_amount' => 74.5,
        ]))
        ->assertSessionHasNoErrors();

    $sale = $this->sale->fresh();

    expect((float) $sale->discount_amount)->toBe(45.5)
        ->and((float) $sale->total_payable)->toBe(474.5);
});

it('rejects a negative discount amount', function () {
    $this->actingAs($this->admin)
        ->postJson('/ship-ticket-sales', discountSalePayload($this->ship, $this->company, $this->admin, [
            'discount_amount' => -5,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('discount_amount');

    expect(ShipTicketSale::count())->toBe(1);
});

it('shows the discount field on the create page and the saved value on the edit page', function () {
    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/create')
        ->assertOk()
        ->assertSee('id="discount_amount"', false);

    $this->sale->update(['discount_amount' => 45.5]);

    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/'.$this->sale->id.'/edit')
        ->assertOk()
        ->assertSee('id="discount_amount"', false)
        ->assertSee('value="45.50"', false);
});

it('reports the total discount amount and the discount column', function () {
    ShipTicketSale::create([
        'customer_name' => 'Reported Discount Customer',
        'customer_mobile' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $this->ship->id,
        'company_id' => $this->company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 20,
        'discount_amount' => 45.5,
        'total_payable' => 474.5,
        'received_amount' => 400,
        'due_amount' => 74.5,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/reports?draw=1&start=0&length=10&start_create_date='.now()->subDays(6)->toDateString().'&end_create_date='.now()->toDateString())
        ->assertOk()
        ->assertJsonPath('totals.total_discount_amount', '45.50')
        ->assertJsonPath('data.0.discount_amount', '45.50');

    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk()
        ->assertSee('Discount Amount', false)
        ->assertSee('id="totalDiscountAmount"', false);
});
