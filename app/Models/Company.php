<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{

    protected $table = 'company';
    protected $fillable = ['name', 'status'];

    public $timestamps = true;

    public function shipTicketSales()
    {
        return $this->hasMany(ShipTicketSale::class, 'company_id', 'id');
    }
}
