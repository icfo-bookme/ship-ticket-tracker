<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Requested = 'requested';
    case PartnerApproved = 'partner_approved';
    case PaymentDetailsAdded = 'payment_details_added';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
