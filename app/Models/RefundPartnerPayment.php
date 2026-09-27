<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundPartnerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'requested_amount',
        'received_amount',
        'payment_method',
        'transaction_id',
        'payment_proof',
        'requested_at',
        'received_at',
        'status',
        'remark',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}
