<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the RBAC system: every permission, the default roles and
     * their permission assignments. Safe to run multiple times.
     */
    public function run(): void
    {
        // 1. Create all application permissions (idempotent).
        foreach (config('roles.permissions') as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Status-wise sales list permissions, derived from config/sales.php so a
        // newly configured status never ends up without its permission.
        foreach (array_keys(config('sales.statuses')) as $status) {
            Permission::firstOrCreate([
                'name' => "sales.status.{$status}",
                'guard_name' => 'web',
            ]);
        }

        // 2. Create the default roles and sync their permissions.
        foreach (config('roles.default_roles') as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions(
                $permissions === '*'
                    ? Permission::all()
                    : $permissions
            );
        }

        $this->command?->info('Roles and permissions seeded: '
            .Permission::count().' permissions, '
            .Role::count().' roles.');
    }
}
