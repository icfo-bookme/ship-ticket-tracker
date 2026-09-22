<?php

namespace App\Services\Admin;

use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function create(array $data): Permission
    {
        return Permission::create(['name' => $data['name'], 'guard_name' => 'web']);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update(['name' => $data['name']]);

        return $permission->fresh('roles:id,name');
    }

    public function canDelete(Permission $permission): bool
    {
        return $permission->roles()->count() === 0;
    }
}
