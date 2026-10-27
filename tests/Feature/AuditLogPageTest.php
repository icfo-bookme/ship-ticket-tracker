<?php

use App\Models\AuditLog;
use App\Models\ShipTicketSale;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->saleLogAttributes = [
        'auditable_type' => (new ShipTicketSale)->getMorphClass(),
        'auditable_id' => 77,
        'context' => ['sale_id' => 77],
    ];
});

it('lets the admin open the audit log from the admin sidebar', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->count(21)->create(['user_name' => 'History Operator', 'context' => ['sale_id' => 77]]);

    $this->actingAs($admin)->get(route('audit-logs.index'))->assertOk()
        ->assertSee('href="'.route('audit-logs.index').'"', false)
        ->assertSee('Audit Log')->assertSee('id="auditLogsTable"', false)
        ->assertSee('Activity')->assertDontSee('>Before<', false)->assertDontSee('>After<', false);

    $this->getJson(route('audit-logs.index', ['draw' => 1, 'start' => 20, 'length' => 10]))->assertOk()
        ->assertJsonPath('recordsTotal', 21)->assertJsonPath('recordsFiltered', 21)
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_name', 'History Operator')
        ->assertJsonPath('data.0.sale_id', 77);
});

it('blocks audit access and hides its sidebar link without audit permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));

    $this->actingAs($user)->get(route('audit-logs.index'))->assertForbidden();
    $this->get('/dashboard')->assertOk()->assertDontSee('href="'.route('audit-logs.index').'"', false);
});

it('shows the admin audit menu to an audit reader without excel access', function () {
    $user = User::factory()->create();
    foreach (['sales.view', 'sales.audit.view'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user)->get(route('audit-logs.index'))->assertOk()
        ->assertSee('id="admin-dropdown"', false)->assertSee('href="'.route('audit-logs.index').'"', false)
        ->assertSee('id="auditLogsTable"', false);
    $this->getJson(route('audit-logs.index'))->assertOk()->assertJsonCount(0, 'data');
});

it('returns a brief update summary without sending customer values or snapshots', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['user_name' => 'Older operator']);
    AuditLog::factory()->state($this->saleLogAttributes)->create([
        'user_name' => '<script>alert(1)</script>',
        'old_values' => ['discount_amount' => '100.00', 'customer_name' => 'Original customer'],
        'new_values' => ['discount_amount' => '200.00', 'customer_name' => '<img src=x onerror=alert(1)>'],
    ]);
    $response = $this->actingAs($admin)->getJson(route('audit-logs.index'))->assertOk()
        ->assertJsonPath('data.0.user_name', '<script>alert(1)</script>')
        ->assertJsonPath('data.0.summary', 'Updated: Discount Amount, Customer Name');

    expect(array_keys($response->json('data.0')))->toBe(['created_at', 'user_name', 'sale_id', 'summary'])
        ->and($response->getContent())->not->toContain('Original customer', '<img src=x onerror=alert(1)>', 'before_html', 'after_html');
});

it('shows only sale creation and update activities with a short creation message', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['event' => 'created', 'user_name' => 'Sale Creator']);
    AuditLog::factory()->state($this->saleLogAttributes)->create(['event' => 'deleted']);
    AuditLog::factory()->create(['event' => 'created']);

    $this->actingAs($admin)->getJson(route('audit-logs.index'))->assertOk()
        ->assertJsonPath('recordsTotal', 1)->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sale_id', 77)->assertJsonPath('data.0.user_name', 'Sale Creator')
        ->assertJsonPath('data.0.summary', 'Sale created');
});

it('searches visible users and changed field labels while keeping the total count', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['user_name' => 'Matching operator', 'new_values' => ['customer_name' => 'Unique Customer']]);
    AuditLog::factory()->state($this->saleLogAttributes)->create(['user_name' => 'Other operator']);

    foreach (['Matching operator', 'Customer Name', 'Updated: Customer Name'] as $search) {
        $this->actingAs($admin)->getJson(route('audit-logs.index', ['search' => ['value' => $search]]))
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 1)
            ->assertJsonCount(1, 'data');
    }

    $this->getJson(route('audit-logs.index', ['search' => ['value' => 'Unique Customer']]))
        ->assertOk()->assertJsonPath('recordsFiltered', 0);
});

it('keeps tied timestamps in a stable order across every page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    $expected = [];
    foreach (range(1, 25) as $index) {
        AuditLog::factory()->state($this->saleLogAttributes)->create(['user_name' => 'Operator '.$index, 'created_at' => '2026-10-27 00:00:00']);
        $expected[] = 'Operator '.$index;
    }
    $received = [];
    foreach ([0, 10, 20] as $offset) {
        $response = $this->actingAs($admin)->getJson(route('audit-logs.index', ['start' => $offset, 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsTotal', 25)->assertJsonPath('recordsFiltered', 25);
        $received = [...$received, ...array_column($response->json('data'), 'user_name')];
    }
    expect($received)->toBe(array_reverse($expected))->and(array_unique($received))->toHaveCount(25);
    $this->getJson(route('audit-logs.index', ['start' => 100]))->assertOk()
        ->assertJsonCount(0, 'data')->assertJsonPath('recordsTotal', 25);
});

it('caps oversized pages at one hundred rows and safely handles malformed search', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->count(105)->create();
    $this->actingAs($admin)->getJson(route('audit-logs.index', ['length' => 1000, 'draw' => 7, 'search' => ['value' => ['invalid']]]))
        ->assertOk()->assertJsonCount(100, 'data')->assertJsonPath('draw', 7)->assertJsonPath('recordsFiltered', 105);
});

it('searches sale ids and creation activity without including other sales', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['event' => 'created']);
    AuditLog::factory()->state($this->saleLogAttributes)->create(['auditable_id' => 88, 'context' => ['sale_id' => 88]]);
    foreach (['#77', 'Sale created'] as $search) {
        $this->actingAs($admin)->getJson(route('audit-logs.index', ['search' => ['value' => $search]]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.sale_id', 77);
    }
});

it('searches dates using the displayed Bangladesh calendar day', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['created_at' => '2026-10-26 19:00:00', 'user_name' => 'Local October 27']);
    AuditLog::factory()->state($this->saleLogAttributes)->create(['created_at' => '2026-10-27 20:00:00', 'user_name' => 'Local October 28']);
    foreach (['2026-10-27', '27 Oct 2026'] as $search) {
        $this->actingAs($admin)->getJson(route('audit-logs.index', ['search' => ['value' => $search]]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.user_name', 'Local October 27');
    }
});

it('searches the public booking label and handles invalid dates without errors', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->create(['event' => 'created', 'user_name' => null]);
    AuditLog::factory()->state($this->saleLogAttributes)->create(['user_name' => 'Named Operator']);

    foreach (['System', 'public booking'] as $search) {
        $this->actingAs($admin)->getJson(route('audit-logs.index', ['search' => ['value' => $search]]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.user_name', 'System / public booking');
    }
    $this->getJson(route('audit-logs.index', ['search' => ['value' => '2026-02-30']]))
        ->assertOk()->assertJsonPath('recordsFiltered', 0);
});

it('bounds audit pagination for invalid lengths and ignores client ordering', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));
    AuditLog::factory()->state($this->saleLogAttributes)->count(12)->create();

    $this->actingAs($admin)->getJson(route('audit-logs.index', [
        'start' => -5, 'length' => -1, 'order' => [['column' => 0, 'dir' => 'invalid']],
    ]))->assertOk()->assertJsonCount(10, 'data');
    $this->getJson(route('audit-logs.index', ['length' => 1000, 'search' => ['value' => null]]))
        ->assertOk()->assertJsonCount(12, 'data');
});
