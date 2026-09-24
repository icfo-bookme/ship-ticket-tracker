<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pendingOnly = User::factory()->create();
    $pendingRole = Role::findOrCreate('Pending Only', 'web');
    $pendingRole->syncPermissions([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.status.pending', 'web'),
    ]);
    $this->pendingOnly->assignRole($pendingRole);

    $this->viewOnly = User::factory()->create();
    $viewRole = Role::findOrCreate('View Only', 'web');
    $viewRole->syncPermissions([Permission::findOrCreate('sales.view', 'web')]);
    $this->viewOnly->assignRole($viewRole);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::findOrCreate('Admin', 'web'));
});

function statusTabs(string $html): string
{
    $start = strpos($html, 'id="statusTabs"');
    $end = $start === false ? false : strpos($html, '</div>', $start);

    expect($start)->not->toBeFalse()
        ->and($end)->not->toBeFalse();

    return substr($html, $start, $end - $start);
}

it('lets a user open only the status lists they are permitted to see', function () {
    $this->actingAs($this->pendingOnly)->get('/sales/status/pending')->assertOk();
    $this->actingAs($this->pendingOnly)->getJson('/sales/pending?draw=1&start=0&length=10')->assertOk();

    $this->actingAs($this->pendingOnly)->get('/sales/status/ticket-issued')->assertForbidden();
    $this->actingAs($this->pendingOnly)->getJson('/sales/ticket-issued?draw=1&start=0&length=10')->assertForbidden();
});

it('guards the sales index route with the pending status permission', function () {
    $this->actingAs($this->pendingOnly)->get('/ship-ticket-sales')->assertOk();
    $this->actingAs($this->viewOnly)->get('/ship-ticket-sales')->assertForbidden();
});

it('shows only the status tabs the user may access', function () {
    $html = $this->actingAs($this->pendingOnly)
        ->get('/sales/status/pending')
        ->assertOk()
        ->getContent();

    $tabs = statusTabs($html);

    expect($tabs)->toContain('Pending')
        ->and($tabs)->not->toContain('Payment Verified')
        ->and($tabs)->not->toContain('Ticket Issued');
});

it('lets the super admin open every status list', function () {
    foreach (array_keys(config('sales.statuses')) as $status) {
        $this->actingAs($this->superAdmin)->get('/sales/status/'.$status)->assertOk();
    }
});

it('creates a status permission for every configured sale status', function () {
    $this->seed(RolePermissionSeeder::class);

    foreach (array_keys(config('sales.statuses')) as $status) {
        expect(Permission::where('name', 'sales.status.'.$status)->exists())->toBeTrue();
    }
});
