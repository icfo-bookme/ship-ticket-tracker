<?php

use App\Models\AuditLog;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $ship = Ship::create(['name' => 'Access Ship', 'status' => 1]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'History Customer', 'customer_mobile' => '01712345678',
        'sales_source' => 'Direct', 'ship_id' => $ship->id, 'journey_date' => '2026-10-28',
        'issued_date' => '2026-10-27', 'status' => 'pending',
    ]);
    $this->log = AuditLog::factory()->create([
        'auditable_type' => $this->sale->getMorphClass(), 'auditable_id' => $this->sale->id,
        'context' => ['sale_id' => $this->sale->id],
    ]);
    $this->url = route('ship-ticket-sales.audit-logs', ['sale' => $this->sale->id]);
});

it('requires authentication to read sale history', function () {
    $this->getJson($this->url)->assertUnauthorized();
});

it('requires both sale access and audit access', function (array $permissions) {
    foreach ($permissions as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $this->actingAs($this->user)->getJson($this->url)->assertForbidden();
})->with([
    'neither permission' => [[]],
    'sale access only' => [['sales.view']],
    'audit access only' => [['sales.audit.view']],
]);

it('returns only the selected sales history including deleted related records', function () {
    foreach (['sales.view', 'sales.audit.view'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    AuditLog::factory()->create(['context' => ['sale_id' => $this->sale->id + 100]]);
    $deletedRefundLog = AuditLog::factory()->create([
        'auditable_type' => (new \App\Models\Refund)->getMorphClass(),
        'auditable_id' => 9999, 'context' => ['sale_id' => $this->sale->id], 'event' => 'deleted',
    ]);
    $this->sale->delete();

    $this->actingAs($this->user)->getJson($this->url)->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonPath('data.0.id', $deletedRefundLog->id)
        ->assertJsonPath('data.1.id', $this->log->id);
});

it('prevents model updates and deletions of audit history', function () {
    expect(fn () => $this->log->update(['event' => 'deleted']))->toThrow(LogicException::class)
        ->and(fn () => $this->log->delete())->toThrow(LogicException::class)
        ->and($this->log->fresh()->event)->toBe('updated');
});

it('denies audit modification abilities to an audit reader', function () {
    foreach (['sales.view', 'sales.audit.view'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    expect(Gate::forUser($this->user)->allows('view', $this->log))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('update', $this->log))->toBeFalse()
        ->and(Gate::forUser($this->user)->allows('delete', $this->log))->toBeFalse();
});

it('renders escaped history values and paginates the history panel', function () {
    foreach (['sales.view', 'sales.audit.view'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    AuditLog::factory()->count(20)->create([
        'auditable_type' => $this->sale->getMorphClass(), 'auditable_id' => $this->sale->id,
        'context' => ['sale_id' => $this->sale->id],
        'user_name' => '<script>alert(1)</script>',
        'old_values' => ['discount_amount' => '100.00'],
        'new_values' => ['discount_amount' => '200.00', 'customer_name' => '<img src=x onerror=alert(1)>'],
    ]);
    $this->actingAs($this->user)->get($this->url)->assertOk()
        ->assertSee('Before')->assertSee('After')->assertSee('Discount Amount')
        ->assertSee('100.00')->assertSee('200.00')
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertSee('Page 1 of 2')->assertSee('data-audit-page', false);

    $this->getJson($this->url.'?page=2')->assertOk()->assertJsonCount(1, 'data');
});

it('shows the history panel only to audit readers', function () {
    $this->user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));
    $this->user->givePermissionTo(Permission::findOrCreate('sales.pending.edit', 'web'));
    $page = '/ship-ticket-sales/'.$this->sale->id;
    $this->actingAs($this->user)->get($page)->assertOk()->assertDontSee('id="saleAuditHistory"', false);

    $this->user->givePermissionTo(Permission::findOrCreate('sales.audit.view', 'web'));
    $this->get($page)->assertOk()->assertSee('id="saleAuditHistory"', false)->assertSee('Edit History');
});
