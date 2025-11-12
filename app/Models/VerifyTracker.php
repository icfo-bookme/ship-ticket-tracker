<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyTracker extends Model
{
    use HasFactory;

    protected $table = 'verify_tracker';

    
    protected $fillable = [
        'name',
        'verified_by',
        'ticket_id',
    ];

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
