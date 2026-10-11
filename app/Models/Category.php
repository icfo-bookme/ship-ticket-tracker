<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ticket_id',
        'package_id',
        'quantity',
        'type',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public $timestamps = true;

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(ShipTicketSale::class, 'ticket_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ShipPackage::class, 'package_id');
    }
}
