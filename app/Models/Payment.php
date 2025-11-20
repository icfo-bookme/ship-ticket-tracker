<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'sales_id',
        'payment_method',
        'received_amount',
        'remark',
    ];

    /**
     * Relation: Each payment belongs to a sale
     */
    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id');
    }
}
