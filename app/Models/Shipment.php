<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $table = 'shipments';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ticket_id',
        'shipment_id',
    ];

    public $timestamps = true;

    
}
