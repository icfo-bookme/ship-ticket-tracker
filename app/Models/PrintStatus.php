<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintStatus extends Model
{
    use HasFactory;

    // Table name (optional if Laravel naming convention is followed)
    protected $table = 'print_status';

    // Primary key (optional if 'id')
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'sales_id',
        'total_printed_number',
    ];

    // Timestamps are enabled by default
    public $timestamps = true;

    // If you want, you can define relationships here
    // Example: linking to a Sale model
    public function sale()
    {
        return $this->belongsTo(ShipTicketSale::class, 'sales_id');
    }
}
