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
        'ticket_fee' => 500,
        'other_fee' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'sold_by' => $this->seller->id,
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);
});

it('shows the sold by user name in a locked field and hides the customer id field', function () {
    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/'.$this->sale->id.'/edit')
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
            'ship_id' => $this->ship->id,
            'company_id' => $this->company->id,
            'journey_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'number_of_ticket' => 1,
            'ticket_fee' => 500,
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
