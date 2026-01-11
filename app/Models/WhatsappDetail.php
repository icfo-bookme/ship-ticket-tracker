<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappDetail extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_details';

    protected $fillable = [
        'tag',
        'whatsapp_number',
        'form_no',
        'url',
    ];

    public $timestamps = true;
}
