<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;


    protected $table = 'categories';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ticket_id',
        'package_id',
        'type',
    ];


    public $timestamps = true;

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
