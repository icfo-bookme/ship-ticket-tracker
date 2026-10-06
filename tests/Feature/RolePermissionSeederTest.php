<?php

use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('seeding adds default roles without overwriting customized role permissions', function () {
    $customRole = Role::create(['name' => 'Agent', 'guard_name' => 'web']);
    $reportView = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    $customRole->givePermissionTo($reportView);

    $this->seed(RolePermissionSeeder::class);

    $customRole = $customRole->fresh('permissions');

    expect($customRole->permissions->pluck('name')->all())->toBe(['reports.view'])
        ->and(Permission::where('name', 'sales.mark_collected')->exists())->toBeTrue()
        ->and(Role::where('name', 'Admin')->exists())->toBeTrue();
});

test('report viewers cannot execute extra payment mutations', function () {
    $user = \App\Models\User::factory()->create();
    $reportView = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    $user->givePermissionTo($reportView);

    $this->actingAs($user)
        ->postJson('/extra-received/1/adjust-to-other-fee')
        ->assertForbidden();
});

test('create-only roles can reach the create screen without reading table data', function () {
    $user = \App\Models\User::factory()->create();
    $createPermission = Permission::firstOrCreate(['name' => 'companies.create', 'guard_name' => 'web']);
    $user->givePermissionTo($createPermission);

    $this->actingAs($user)
        ->get('/companies-details')
        ->assertOk()
        ->assertSee('data-permission="companies.create"', false)
        ->assertDontSee('id="companiesTable"', false);

    $this->actingAs($user)
        ->getJson('/companies?draw=1&start=0&length=10')
        ->assertForbidden();
});

test('sales list permission does not authorize payment verification', function () {
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo([
        Permission::firstOrCreate(['name' => 'sales.view', 'guard_name' => 'web']),
        Permission::firstOrCreate(['name' => 'sales.status.pending', 'guard_name' => 'web']),
    ]);

    $this->actingAs($user)
        ->putJson('/sale/verify/1/payment-verified')
        ->assertForbidden();
});
