<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Detail Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => Ship::create(['name' => 'MV Detail Ship', 'status' => 1])->id,
        'company_id' => Company::create(['name' => 'Detail Company', 'status' => 1])->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'payment-verified',
        'sold_by' => $this->admin->id,
    ]);

    $package = ShipPackage::create([
        'ship_id' => $this->sale->ship_id,
        'name' => 'Deck',
        'price' => 500,
    ]);

    Category::create([
        'ticket_id' => $this->sale->id,
        'package_id' => $package->id,
        'quantity' => 1,
        'type' => 'departure',
    ]);
});

it('renders the sale detail page with the printed ticket context', function () {
    PrintedTicket::create([
        'sales_id' => $this->sale->id,
        'filename' => '01712345678-3.pdf',
        'group_by_id' => $this->sale->id,
    ]);

    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/'.$this->sale->id)
        ->assertOk()
        ->assertSee('Ship Ticket Sale #'.$this->sale->id)
        ->assertSee('value="01712345678-4"', false);
});
