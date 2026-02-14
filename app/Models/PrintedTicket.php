<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintedTicket extends Model
{
    // Table name
    protected $table = 'printed_tickets';

    // Mass assignable fields
    protected $fillable = [
        'sales_id',
        'filename',
        'group_by_id',
    ];

    // Enable timestamps (created_at, updated_at)
    public $timestamps = true;

    /**
     * Relationship: PrintedTicket belongs to a Sale
     */
    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id', 'id');
    }
}
