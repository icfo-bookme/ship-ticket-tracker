<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('roles.permissions', []) as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach (array_keys(config('sales.statuses', [])) as $status) {
            Permission::firstOrCreate([
                'name' => "sales.status.{$status}",
                'guard_name' => 'web',
            ]);
        }

        $legacyPermissionMap = [
            'sales.create' => ['sale_drafts.view', 'sale_drafts.create'],
            'sales.view' => ['payments.proof.view', 'notifications.view'],
            'sales.verify' => [
                'sales.issue', 'sales.mark_printed', 'sales.create_parcel',
                'sales.mark_shipped', 'sales.mark_collected', 'sales.bftn.receive',
                'notifications.verify',
            ],
            'refunds.manage' => [
                'refunds.view', 'refunds.create', 'refunds.edit', 'refunds.delete', 'refunds.cancel',
                'refunds.approve', 'refunds.payment_details', 'refunds.customer_payment',
            ],
            'ships.manage' => ['ships.view', 'ships.create', 'ships.edit', 'ships.delete'],
            'companies.manage' => ['companies.view', 'companies.create', 'companies.edit', 'companies.delete'],
            'packages.manage' => ['packages.view', 'packages.create', 'packages.edit', 'packages.delete'],
            'payments.manage' => ['payments.due.collect'],
            'cash.manage' => ['cash_collections.view', 'cash_collections.create', 'cash_collections.edit', 'cash_collections.delete'],
            'users.manage' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
            'roles.manage' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
            'permissions.manage' => ['permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'],
            'whatsapp.manage' => ['whatsapp.view', 'whatsapp.create', 'whatsapp.edit', 'whatsapp.delete'],
            'excel.manage' => ['excel.view', 'excel.create', 'excel.edit', 'excel.delete'],
            'reports.view' => ['extra_received.view'],
        ];

        foreach ($legacyPermissionMap as $legacyName => $newNames) {
            $legacyPermission = Permission::query()
                ->where('name', $legacyName)
                ->where('guard_name', 'web')
                ->first();

            if (! $legacyPermission) {
                continue;
            }

            $legacyPermission->roles->each(function ($role) use ($newNames): void {
                $role->givePermissionTo($newNames);
            });
        }

        Permission::query()
            ->whereIn('name', ['dashboard.view', 'documentation.view'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Permission $permission): void {
                Role::query()
                    ->where('guard_name', 'web')
                    ->get()
                    ->each(fn ($role) => $role->givePermissionTo($permission));
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permission assignments may have been customized after this migration.
    }
};
