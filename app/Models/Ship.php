<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ship extends Model
{
    protected $table = 'ships';

    protected $fillable = [
        'name',
        'route',
        'status'
    ];
    
     public $timestamps = true;
}
