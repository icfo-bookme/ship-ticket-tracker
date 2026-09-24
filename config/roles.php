<?php

// Role & permission configuration for the RBAC system.

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    |
    | Users holding this role bypass ALL permission checks (Gate::before).
    | This role should never be deleted or renamed from the admin UI.
    |
    */

    'super_admin_role' => 'Admin',

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Every permission referenced by the `can:` middleware in routes/web.php.
    | The RolePermissionSeeder creates all of these.
    |
    */

    'permissions' => [
        // Sales module
        'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.verify',

        // Sales — per status list access, checked by the `sales.status` middleware.
        // Keep in sync with the `statuses` list in config/sales.php.
        'sales.status.pending', 'sales.status.payment-verified', 'sales.status.ticket-issued',
        'sales.status.ticket-printed', 'sales.status.shipment_id_entered', 'sales.status.shipped',
        'sales.status.partial-refunded', 'sales.status.refunded',

        // Refunds module
        'refunds.view', 'refunds.manage',

        // Master data modules
        'ships.manage', 'companies.manage', 'packages.manage',

        // Accounting modules
        'reports.view', 'payments.manage', 'cash.manage',

        // Admin modules
        'whatsapp.manage', 'excel.manage',
        'users.manage', 'roles.manage', 'permissions.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Starter roles created by the RolePermissionSeeder and the permissions
    | each one receives. "*" means every permission (super admin also
    | bypasses gates via Gate::before).
    |
    */

    'default_roles' => [
        'Admin' => '*',

        'Manager' => [
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.verify',
            'sales.status.pending', 'sales.status.payment-verified', 'sales.status.ticket-issued',
            'sales.status.ticket-printed', 'sales.status.shipment_id_entered', 'sales.status.shipped',
            'sales.status.partial-refunded', 'sales.status.refunded',
            'refunds.view', 'refunds.manage',
            'ships.manage', 'companies.manage', 'packages.manage',
            'reports.view', 'payments.manage', 'cash.manage',
            'whatsapp.manage',
        ],

        'Agent' => [
            'sales.view', 'sales.create', 'sales.edit', 'sales.status.pending',
        ],

        'Viewer' => [
            'sales.view', 'sales.status.pending',
        ],
    ],

];
