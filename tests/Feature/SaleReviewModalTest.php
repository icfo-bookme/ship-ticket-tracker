<?php

use App\Models\Company;
use App\Models\Ship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Review Seller']);
    $this->admin->assignRole(Role::findOrCreate('Admin', 'web'));

    Ship::create(['name' => 'MV Review Ship', 'status' => 1]);
    Company::create(['name' => 'Review Company', 'status' => 1]);
});

it('gives the review modal the seller name while still posting the seller id', function () {
    $this->actingAs($this->admin)
        ->get('/ship-ticket-sales/create')
        ->assertOk()
        ->assertSee('ship-ticket-create-page', false)
        ->assertSee('bg-white p-3 sm:p-4 md:p-6', false)
        ->assertSee('max-h-[calc(100dvh-1rem)]', false)
        ->assertSee('id="cancelDraftButton"', false)
        ->assertSee('ship-ticket-sales', false)
        ->assertSee('data-seller-name="Review Seller"', false)
        ->assertSee('name="sold_by" value="'.$this->admin->id.'"', false);
});

it('shows the seller name, discount and due amounts in the review modal script', function () {
    $script = file_get_contents(resource_path('js/pages/ship-ticket-sales.js'));

    expect($script)->toContain('dataset.sellerName')
        ->and($script)->toContain('field === "sold_by"')
        ->and($script)->toContain('field === "discount_amount" || field === "due_amount"')
        ->and($script)->toContain("document.readyState === 'loading'")
        ->and($script)->toContain("this.addEventListener('cancelDraftButton', 'click', () => this.cancelDraft())")
        ->and($script)->toContain('text-red-600');
});

it('keeps the ticket creation page white and stacks payment rows on small screens', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.ship-ticket-create-page #ticketForm input:not([type="checkbox"]):not([type="radio"])')
        ->toContain('.ship-ticket-create-page #paymentInfoWrapper .payment-entry > div')
        ->toContain('grid-column: 1 / -1;');
});
