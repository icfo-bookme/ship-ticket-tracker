<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';

    protected $fillable = [
        'sales_id',
        'refunded_number_of_tickets',
        'refunded_amount',
    ];

    public $timestamps = true;
}
