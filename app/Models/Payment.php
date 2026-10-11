<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'sales_id',
        'payment_method',
        'is_bftn',
        'settlement_status',
        'received_amount',
        'remark',
        'paid_date',
        'transaction_id',
        'payment_proof',
        'payment_datetime',
        'bftn_issue_datetime',
        'received_at',
        'bftn_notification_read_at',
    ];

    protected $casts = [
        'is_bftn' => 'boolean',
        'bftn_issue_datetime' => 'datetime',
        'received_at' => 'datetime',
        'bftn_notification_read_at' => 'datetime',
    ];

    /**
     * Relation: Each payment belongs to a sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id')->withTrashed();
    }
}
