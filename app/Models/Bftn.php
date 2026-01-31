<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bftn extends Model
{
    use HasFactory;

    protected $table = 'bftn';

    protected $primaryKey = 'id';

    protected $fillable = [
        'sales_id',
        'bftn_date_time',
    ];

    public $timestamps = true;

    /**
     * Relationship: BFTN belongs to a Sale
     */
    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id', 'id');
    }
}
