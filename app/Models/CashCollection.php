<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashCollection extends Model
{
    use HasFactory;

    protected $table = 'cash_collections';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'entry_by',
        'cashout_amount',
    ];
}
