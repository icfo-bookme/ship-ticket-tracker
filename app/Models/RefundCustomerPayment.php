<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundCustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_proof',
        'paid_at',
        'status',
        'remark',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}
