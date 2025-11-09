<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipPackage extends Model
{
    use HasFactory;

    protected $table = 'ship_packages';

    
    protected $fillable = [
        'ship_id',
        'name',
    ];

   
    public $timestamps = true;

    public function ship()
    {
        return $this->belongsTo(Ship::class, 'ship_id');
    }
}
