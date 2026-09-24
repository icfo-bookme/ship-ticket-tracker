<?php

use App\Enums\SaleStatus;

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
        SaleStatus::Pending->value => SaleStatus::Pending->label(),
        SaleStatus::PaymentVerified->value => SaleStatus::PaymentVerified->label(),
        SaleStatus::TicketIssued->value => SaleStatus::TicketIssued->label(),
        SaleStatus::TicketPrinted->value => SaleStatus::TicketPrinted->label(),
        SaleStatus::ShipmentIdEntered->value => SaleStatus::ShipmentIdEntered->label(),
        SaleStatus::Shipped->value => SaleStatus::Shipped->label(),
        SaleStatus::PartialRefunded->value => SaleStatus::PartialRefunded->label(),
        SaleStatus::Refunded->value => SaleStatus::Refunded->label(),
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
        SaleStatus::Pending->value,
        SaleStatus::PaymentVerified->value,
        SaleStatus::TicketIssued->value,
        SaleStatus::TicketPrinted->value,
        SaleStatus::ShipmentIdEntered->value,
        SaleStatus::Shipped->value,
    ],
];
