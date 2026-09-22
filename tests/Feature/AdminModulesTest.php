<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // The data-conversion migration already creates the Admin role,
    // so find it or create it if missing.
    $this->adminRole = Role::findOrCreate('Admin', 'web');
    $this->agentRole = Role::create(['name' => 'Agent', 'guard_name' => 'web']);

    Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
    Permission::create(['name' => 'roles.manage', 'guard_name' => 'web']);
    Permission::create(['name' => 'permissions.manage', 'guard_name' => 'web']);
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->plain = User::factory()->create();
});

test('super admin can access admin pages', function () {
    $this->actingAs($this->admin)
        ->get('/users-details')->assertOk();
    $this->actingAs($this->admin)
        ->get('/roles-details')->assertOk();
    $this->actingAs($this->admin)
        ->get('/permissions-details')->assertOk();
});

test('user without permission cannot access admin pages', function () {
    $this->actingAs($this->plain)
        ->get('/users-details')->assertForbidden();
    $this->actingAs($this->plain)
        ->get('/roles-details')->assertForbidden();
    $this->actingAs($this->plain)
        ->get('/permissions-details')->assertForbidden();
});

test('role can be created with permissions via api', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/roles', [
            'name' => 'Manager',
            'permissions' => ['reports.view'],
        ]);

    $response->assertCreated();

    $role = Role::findByName('Manager');
    expect($role->hasPermissionTo('reports.view'))->toBeTrue();
});

test('role name must be unique', function () {
    $this->actingAs($this->admin)
        ->postJson('/roles', ['name' => 'Agent'])
        ->assertStatus(422);
});

test('super admin role cannot be deleted', function () {
    $this->actingAs($this->admin)
        ->deleteJson('/roles/'.$this->adminRole->id)
        ->assertForbidden();

    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
});

test('permission can be created and duplicate is rejected', function () {
    $this->actingAs($this->admin)
        ->postJson('/permissions', ['name' => 'sales.create'])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson('/permissions', ['name' => 'sales.create'])
        ->assertStatus(422);
});

test('permission assigned to a role cannot be deleted', function () {
    $this->agentRole->givePermissionTo('reports.view');

    $permission = Permission::where('name', 'reports.view')->first();

    $this->actingAs($this->admin)
        ->deleteJson('/permissions/'.$permission->id)
        ->assertStatus(422);
});

test('user can be created with a role via api', function () {
    $this->actingAs($this->admin)
        ->postJson('/users', [
            'name' => 'New Agent',
            'email' => 'agent@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'Agent',
        ])
        ->assertCreated();

    $user = User::where('email', 'agent@example.com')->first();
    expect($user->hasRole('Agent'))->toBeTrue();
});

test('user role can be reassigned via update', function () {
    $response = $this->actingAs($this->admin)
        ->putJson('/users/'.$this->plain->id, [
            'name' => $this->plain->name,
            'email' => $this->plain->email,
            'role' => 'Agent',
        ]);

    $response->assertOk();
    expect($this->plain->fresh()->hasRole('Agent'))->toBeTrue();
});

test('super admin and self accounts are protected from deletion', function () {
    // Plain user cannot delete anyone (no users.manage permission)
    $this->actingAs($this->plain)
        ->deleteJson('/users/'.$this->admin->id)
        ->assertForbidden();

    // Admin cannot delete own account
    $this->actingAs($this->admin)
        ->deleteJson('/users/'.$this->admin->id)
        ->assertForbidden();

    expect(User::where('email', $this->admin->email)->exists())->toBeTrue();
});

test('gated module route denies plain user and allows admin', function () {
    $this->actingAs($this->plain)
        ->get('/admin/sales-reports')
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get('/admin/sales-reports')
        ->assertOk();
});

test('sidebar hides admin sections for users without permission', function () {
    // NOTE: /dashboard is MySQL-specific (DATE_FORMAT) and fails on the sqlite
    // test DB, so we use /profile which renders the same layout + sidebar.
    $response = $this->actingAs($this->plain)->get('/profile');

    $response->assertOk();
    // Admin section should not be rendered for a user without permissions
    $response->assertDontSee('/roles-details', false);
    $response->assertDontSee('/users-details', false);
});

test('sidebar shows admin sections for super admin', function () {
    $response = $this->actingAs($this->admin)->get('/profile');

    $response->assertOk();
    $response->assertSee('/roles-details', false);
    $response->assertSee('/users-details', false);
    $response->assertSee('/permissions-details', false);
});

test('role permission seeder creates permissions and default roles', function () {
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder'])->assertSuccessful();

    foreach (config('roles.permissions') as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue();
    }

    foreach (array_keys(config('roles.default_roles')) as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }

    $agent = Role::findByName('Agent');
    expect($agent->hasPermissionTo('sales.create'))->toBeTrue()
        ->and($agent->hasPermissionTo('sales.delete'))->toBeFalse()
        ->and($agent->hasPermissionTo('users.manage'))->toBeFalse();

    $manager = Role::findByName('Manager');
    expect($manager->hasPermissionTo('refunds.manage'))->toBeTrue()
        ->and($manager->hasPermissionTo('excel.manage'))->toBeFalse();

    expect(Role::findByName('Admin')->hasPermissionTo('sales.view'))->toBeTrue();
});

test('user:make-admin promotes an existing user', function () {
    $this->artisan('user:make-admin', ['email' => $this->plain->email])
        ->assertSuccessful();

    expect($this->plain->fresh()->hasRole('Admin'))->toBeTrue();
});

test('user:make-admin creates the user when missing', function () {
    $this->artisan('user:make-admin', [
        'email' => 'bootstrap@example.com',
        '--password' => 'bootstrap-pass-123',
    ])->assertSuccessful();

    $user = User::where('email', 'bootstrap@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('Admin'))->toBeTrue()
        ->and(Illuminate\Support\Facades\Hash::check('bootstrap-pass-123', $user->password))->toBeTrue();
});

test('users index returns DataTables server-side envelope', function () {
    User::factory()->count(12)->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/users?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

    $json = $response->json();
    expect($json['recordsTotal'])->toBe(14)          // 12 + admin + plain
        ->and(count($json['data']))->toBe(10)        // paginated page of 10
        ->and($json['data'][0])->toHaveKeys(['id', 'name', 'email', 'roles', 'is_super_admin', 'is_self']);
});

test('users index global search filters rows', function () {
    User::factory()->create(['name' => 'Zebra Person', 'email' => 'zebra@example.com']);

    $response = $this->actingAs($this->admin)
        ->getJson('/users?draw=1&start=0&length=10&search[value]=Zebra');

    $json = $response->json();
    expect($json['recordsFiltered'])->toBe(1)
        ->and($json['data'][0]['email'])->toBe('zebra@example.com');
});

test('users index sorting by name descending works', function () {
    User::factory()->create(['name' => 'Aaron First']);
    User::factory()->create(['name' => 'Zulu Last']);

    $response = $this->actingAs($this->admin)
        ->getJson('/users?draw=1&start=0&length=25&order[0][column]=1&order[0][dir]=desc&columns[1][name]=name');

    $json = $response->json();
    $names = collect($json['data'])->pluck('name');

    // Descending by name: "Zulu Last" must appear before "Aaron First".
    expect($names->search('Zulu Last'))->toBeLessThan($names->search('Aaron First'));
});

test('roles and permissions index return server-side envelopes', function () {
    $roles = $this->actingAs($this->admin)->getJson('/roles?draw=1&start=0&length=10')->json();
    expect($roles)->toHaveKeys(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->and($roles['data'][0])->toHaveKeys(['id', 'name', 'permissions', 'permissions_count', 'is_super_admin']);

    $permissions = $this->actingAs($this->admin)->getJson('/permissions?draw=1&start=0&length=10')->json();
    expect($permissions)->toHaveKeys(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->and($permissions['data'][0])->toHaveKeys(['id', 'name', 'group', 'roles', 'roles_count']);
});
