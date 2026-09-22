<?php

namespace App\Services\Admin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function permissionGroups(): Collection
    {
        return Permission::orderBy('name')->get()
            ->map(fn (Permission $permission): array => [
                'name' => $permission->name,
                'group' => ucfirst(str_contains($permission->name, '.') ? Str::before($permission->name, '.') : 'Other'),
            ])
            ->groupBy('group');
    }

    public function create(array $data): Role
    {
        $role = DB::transaction(function () use ($data): Role {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        if ($role->name === config('roles.super_admin_role')) {
            $data['name'] = $role->name;
        }

        DB::transaction(function () use ($role, $data): void {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return $role->fresh('permissions:id,name');
    }

    public function delete(Role $role): bool
    {
        if ($role->name === config('roles.super_admin_role')) {
            return false;
        }

        $role->delete();

        return true;
    }
}
