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
        'dashboard.view', 'documentation.view',
        'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.verify', 'sales.issue', 'sales.print',
        'sales.mark_printed', 'sales.create_parcel', 'sales.mark_shipped', 'sales.mark_collected', 'sales.bftn.receive',

        // Sales — per status list access, checked by the `sales.status` middleware.
        // Keep in sync with the `statuses` list in config/sales.php.
        'sales.status.pending', 'sales.status.payment-verified', 'sales.status.ticket-issued',
        'sales.status.ticket-printed', 'sales.status.shipment_id_entered', 'sales.status.shipped',
        'sales.status.collect_from_office',
        'sales.status.partial-refunded', 'sales.status.refunded',

        // Refunds module
        'sale_drafts.view', 'sale_drafts.create', 'sale_drafts.edit', 'sale_drafts.delete',
        'payments.due.collect', 'payments.proof.view', 'notifications.view', 'notifications.verify',
        'refunds.view', 'refunds.create', 'refunds.edit', 'refunds.delete', 'refunds.cancel',
        'refunds.approve', 'refunds.payment_details', 'refunds.customer_payment',

        // Master data modules
        'ships.view', 'ships.create', 'ships.edit', 'ships.delete',
        'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
        'packages.view', 'packages.create', 'packages.edit', 'packages.delete',

        // Accounting modules
        'reports.view', 'extra_received.view', 'extra_received.adjust', 'extra_received.refund',
        'cash_collections.view', 'cash_collections.create', 'cash_collections.edit', 'cash_collections.delete',

        // Admin modules
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
        'whatsapp.view', 'whatsapp.create', 'whatsapp.edit', 'whatsapp.delete',
        'excel.view', 'excel.create', 'excel.edit', 'excel.delete',
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
            'dashboard.view', 'documentation.view',
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.verify', 'sales.issue', 'sales.print',
            'sales.mark_printed', 'sales.create_parcel', 'sales.mark_shipped', 'sales.mark_collected', 'sales.bftn.receive',
            'sales.status.pending', 'sales.status.payment-verified', 'sales.status.ticket-issued',
            'sales.status.ticket-printed', 'sales.status.shipment_id_entered', 'sales.status.shipped',
            'sales.status.collect_from_office',
            'sales.status.partial-refunded', 'sales.status.refunded',
            'sale_drafts.view', 'sale_drafts.create', 'sale_drafts.edit', 'sale_drafts.delete',
            'payments.due.collect', 'payments.proof.view', 'notifications.view', 'notifications.verify',
            'refunds.view', 'refunds.create', 'refunds.edit', 'refunds.delete', 'refunds.cancel',
            'refunds.approve', 'refunds.payment_details', 'refunds.customer_payment',
            'ships.view', 'ships.create', 'ships.edit', 'ships.delete',
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
            'packages.view', 'packages.create', 'packages.edit', 'packages.delete',
            'reports.view', 'extra_received.view', 'extra_received.adjust', 'extra_received.refund',
            'cash_collections.view', 'cash_collections.create', 'cash_collections.edit', 'cash_collections.delete',
            'whatsapp.view', 'whatsapp.create', 'whatsapp.edit', 'whatsapp.delete',
            'excel.view', 'excel.create', 'excel.edit', 'excel.delete',
        ],

        'Agent' => [
            'dashboard.view', 'documentation.view', 'sales.view', 'sales.create', 'sales.edit', 'sales.status.pending',
            'sale_drafts.view', 'sale_drafts.create', 'payments.proof.view', 'notifications.view',
        ],

        'Viewer' => [
            'dashboard.view', 'documentation.view', 'sales.view', 'sales.status.pending',
            'sale_drafts.view', 'payments.proof.view', 'notifications.view', 'refunds.view',
            'ships.view', 'companies.view', 'packages.view', 'reports.view', 'extra_received.view',
            'cash_collections.view', 'users.view', 'roles.view', 'permissions.view',
            'whatsapp.view', 'excel.view',
        ],
    ],

];
