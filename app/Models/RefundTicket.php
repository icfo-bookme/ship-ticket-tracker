<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundTicket extends Model
{
    use HasFactory;

    protected $table = 'refund_request_tickets';

    protected $fillable = [
        'refund_id',
        'category_id',
        'category_name',
        'category_type',
        'purchased_quantity',
        'refunded_quantity',
        'unit_amount',
        'gross_amount',
        'remark',
    ];

    protected $casts = [
        'unit_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}
