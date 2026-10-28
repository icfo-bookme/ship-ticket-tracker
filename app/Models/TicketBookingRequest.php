<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketBookingRequest extends Model
{
    /** @use HasFactory<\Database\Factories\TicketBookingRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'mobile',
        'ticket_quantity',
        'package_name',
        'trip_type',
        'journey_date',
        'return_date',
        'status',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'ticket_quantity' => 'integer',
            'journey_date' => 'date',
            'return_date' => 'date',
        ];
    }
}
