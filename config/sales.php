<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ship Ticket Sale Statuses
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the sale lifecycle statuses. Used by the
    | route whitelist (web.php), the status tabs (layouts/tab.blade.php) and
    | the edit form (ship_ticket_sales/sales.blade.php) so that status values
    | and labels stay consistent across the whole application.
    |
    */

    'statuses' => [
        'pending' => 'Pending',
        'payment-verified' => 'Payment Verified',
        'ticket-issued' => 'Ticket Issued',
        'ticket-printed' => 'Ticket Printed',
        'shipment_id_entered' => 'Parcel Created',
        'shipped' => 'Shipped',
        'partial-refunded' => 'Partially Refunded',
        'refunded' => 'Refunded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Tabs
    |--------------------------------------------------------------------------
    |
    | The statuses exposed as navigation tabs on the sales list page, in the
    | order they should appear.
    |
    */

    'tabs' => [
        'pending',
        'payment-verified',
        'ticket-issued',
        'ticket-printed',
        'shipment_id_entered',
        'shipped',
    ],
];
