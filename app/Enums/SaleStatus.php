<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Pending = 'pending';
    case PaymentVerified = 'payment-verified';
    case TicketIssued = 'ticket-issued';
    case TicketPrinted = 'ticket-printed';
    case ShipmentIdEntered = 'shipment_id_entered';
    case Shipped = 'shipped';
    case PartialRefunded = 'partial-refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::PaymentVerified => 'Payment Verified',
            self::TicketIssued => 'Ticket Issued',
            self::TicketPrinted => 'Ticket Printed',
            self::ShipmentIdEntered => 'Parcel Created',
            self::Shipped => 'Shipped',
            self::PartialRefunded => 'Partially Refunded',
            self::Refunded => 'Refunded',
        };
    }
}
