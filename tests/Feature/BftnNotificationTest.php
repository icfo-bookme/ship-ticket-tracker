<?php

use App\Models\Bftn;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $role = Role::findOrCreate('Notification Manager', 'web');
    $role->syncPermissions([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $this->user->assignRole($role);

    $this->ship = Ship::create(['name' => 'MV Notify Ship', 'status' => 1]);
    $this->company = Company::create(['name' => 'Notify Company', 'status' => 1]);
});

function notificationSale(Ship $ship, Company $company): ShipTicketSale
{
    return ShipTicketSale::create([
        'customer_name' => 'Notify Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01812345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'received_amount' => 100,
        'due_amount' => 400,
        'issued_date' => '2026-09-23',
        'number_of_ticket' => 1,
        'status' => 'pending',
    ]);
}

it('returns due bftn notifications with the active count', function () {
    $sale = notificationSale($this->ship, $this->company);

    Bftn::create([
        'sales_id' => $sale->id,
        'bftn_date_time' => now()->subHour(),
        'notifications_status' => 1,
    ]);

    Bftn::create([
        'sales_id' => $sale->id,
        'bftn_date_time' => now()->addDay(),
        'notifications_status' => 1,
    ]);

    $this->actingAs($this->user)
        ->getJson('/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.notification', fn (string $message): bool => str_contains($message, 'Notify Customer (01812345678)'))
        ->assertJsonPath('data.0.redirectUrl', "/ship-ticket-sales/{$sale->id}");
});

it('marks a notification as read', function () {
    $sale = notificationSale($this->ship, $this->company);

    $notification = Bftn::create([
        'sales_id' => $sale->id,
        'bftn_date_time' => now()->subHour(),
        'notifications_status' => 1,
    ]);

    $this->actingAs($this->user)
        ->from('/dashboard')
        ->get("/notification/verify/{$notification->id}")
        ->assertRedirect('/dashboard');

    expect($notification->fresh()->notifications_status)->toBe(0);
});
