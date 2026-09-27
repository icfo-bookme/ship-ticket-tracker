<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'sales.status.collect_from_office',
            'guard_name' => 'web',
        ]);

        $ticketPrintedPermission = Permission::query()
            ->where('name', 'sales.status.ticket-printed')
            ->where('guard_name', 'web')
            ->first();

        if ($ticketPrintedPermission) {
            $ticketPrintedPermission->roles->each(
                fn (Role $role) => $role->givePermissionTo($permission)
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'sales.status.collect_from_office')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            $permission->roles->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }
    }
};
