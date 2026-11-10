<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';

    protected $fillable = [
        'adjustment_started_at',
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
        'adjustment_started_at' => 'datetime',
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

    public static function partnerOutflowSql(): string
    {
        return '(CASE WHEN EXISTS (SELECT 1 FROM company_error_refunds WHERE company_error_refunds.refund_id = refunds.id)
            AND refunds.partner_share_amount > refunds.customer_charge_amount
            THEN refunds.customer_charge_amount ELSE refunds.partner_share_amount END)';
    }

    public function partnerOutflowAmount(): float
    {
        $partnerShare = (float) $this->partner_share_amount;

        return $this->companyErrorRefund
            ? min($partnerShare, (float) $this->customer_charge_amount)
            : $partnerShare;
    }

    public function companyErrorRefund(): HasOne
    {
        return $this->hasOne(CompanyErrorRefund::class);
    }

    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id')->withTrashed();
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

    public function adjustments(): HasMany
    {
        return $this->hasMany(Payment::class, 'refund_id');
    }

    /** @return array{settlement_label: string, refund_adjusted_amount: float, customer_refund_paid: float, remaining_refund_amount: float, adjustment_references: array<int, array{sale_id: int, amount: float}>} */
    public function settlementSummary(): array
    {
        $this->loadMissing(['adjustments', 'customerPayments']);
        $adjusted = round((float) $this->adjustments->sum('received_amount'), 2);
        $paid = round((float) $this->customerPayments->where('status', 'paid')->sum('amount'), 2);
        $label = match (true) {
            $adjusted > 0 && $paid > 0 => 'Partly Refunded & Adjusted',
            $adjusted > 0 => 'Refund Adjusted',
            $paid > 0 => 'Refunded',
            (float) $this->customer_refund_amount <= 0 && (float) $this->due_adjusted_amount > 0 => 'Due Adjusted',
            $this->status === 'completed' => 'Refunded',
            default => 'Pending',
        };

        return [
            'settlement_label' => $label,
            'refund_adjusted_amount' => $adjusted,
            'customer_refund_paid' => $paid,
            'remaining_refund_amount' => round(max((float) $this->customer_refund_amount - $adjusted - $paid, 0), 2),
            'adjustment_references' => $this->adjustments->map(fn (Payment $payment): array => [
                'sale_id' => (int) $payment->sales_id,
                'amount' => round((float) $payment->received_amount, 2),
            ])->values()->all(),
        ];
    }
}
