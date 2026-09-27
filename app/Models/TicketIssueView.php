<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketIssueView extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id',
        'user_id',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
