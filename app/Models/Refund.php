<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';

    protected $fillable = [
        'sales_id',
        'status',
        'batch_uuid',
        'refund_type',
        'reason',
        'refunded_number_of_tickets',
        'refunded_amount',
        'gross_refund_amount',
        'refund_discount_amount',
        'other_fee_deduction',
        'customer_charge_percent',
        'customer_charge_amount',
        'partner_share_percent',
        'partner_share_amount',
        'customer_refund_amount',
        'due_adjusted_amount',
        'company_retained_amount',
        'requested_at',
        'partner_received_at',
        'customer_refunded_at',
        'remark',
        'refund_payment_details',
    ];

    protected $casts = [
        'gross_refund_amount' => 'decimal:2',
        'refund_discount_amount' => 'decimal:2',
        'other_fee_deduction' => 'decimal:2',
        'customer_charge_percent' => 'decimal:2',
        'customer_charge_amount' => 'decimal:2',
        'partner_share_percent' => 'decimal:2',
        'partner_share_amount' => 'decimal:2',
        'customer_refund_amount' => 'decimal:2',
        'due_adjusted_amount' => 'decimal:2',
        'company_retained_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'partner_received_at' => 'datetime',
        'customer_refunded_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id');
    }

    public function tickets()
    {
        return $this->hasMany(RefundTicket::class);
    }

    public function partnerPayments()
    {
        return $this->hasMany(RefundPartnerPayment::class);
    }

    public function customerPayments()
    {
        return $this->hasMany(RefundCustomerPayment::class);
    }

    public $timestamps = true;
}
