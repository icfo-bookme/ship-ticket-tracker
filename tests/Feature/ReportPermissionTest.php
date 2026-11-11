<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

dataset('report permissions', [
    ['reports.sales.view', 'sales.reports', 'reports.data'],
    ['reports.ticket_issued.view', 'ticket-issued-reports.index', 'ticket-issued-reports.data'],
    ['reports.refund.view', 'refund-reports.index', 'refund-reports.data'],
    ['reports.company_loss.view', 'company-loss-reports.index', 'company-loss-reports.data'],
    ['reports.bftn.view', 'bftn-reports.index', 'bftn-reports.data'],
]);

it('isolates each report page data and sidebar by permission', function (string $permission, string $page, string $data) {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    $response = $this->actingAs($user)->get(route($page))->assertOk();
    $this->getJson(route($data))->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $links = $xpath->query('//*[@id="reports-dropdown-list"]//a');
    expect($links->length)->toBe(1)
        ->and($links->item(0)->getAttribute('href'))->toBe(route($page));

    foreach (['sales.reports' => 'reports.data', 'ticket-issued-reports.index' => 'ticket-issued-reports.data', 'refund-reports.index' => 'refund-reports.data', 'company-loss-reports.index' => 'company-loss-reports.data', 'bftn-reports.index' => 'bftn-reports.data'] as $otherPage => $otherData) {
        if ($otherPage !== $page) {
            $this->get(route($otherPage))->assertForbidden();
            $this->getJson(route($otherData))->assertForbidden();
        }
    }
})->with('report permissions');

it('denies old broad permission but preserves admin access', function (string $permission, string $page, string $data) {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));
    $this->actingAs($user)->get(route($page))->assertForbidden();
    $this->getJson(route($data))->assertForbidden();

    $user->assignRole('Admin');
    $this->get(route($page))->assertOk();
    $this->getJson(route($data))->assertOk();
})->with('report permissions');

it('seeds report permissions repeatedly without modifying existing role assignments', function () {
    $permission = Permission::findOrCreate('reports.view', 'web');
    $role = Role::findOrCreate('Manager', 'web');
    $role->givePermissionTo($permission);
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['reports.view']);

    foreach (['reports.sales.view', 'reports.ticket_issued.view', 'reports.refund.view', 'reports.company_loss.view', 'reports.bftn.view'] as $name) {
        expect(Permission::query()->where('name', $name)->where('guard_name', 'web')->count())->toBe(1)
            ->and(Role::findByName('Viewer')->hasPermissionTo($name))->toBeTrue();
    }
});
