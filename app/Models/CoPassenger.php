<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'ship_ticket_sale_id',
        'name',
        'nid',
        'co_passernger_number'
    ];

    /**
     * Relationship: Each co-passenger belongs to one ship ticket sale
     */
    public function ticketSale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'ship_ticket_sale_id');
    }
}
