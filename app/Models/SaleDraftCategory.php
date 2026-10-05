<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDraftCategory extends Model
{
    protected $fillable = [
        'sale_draft_id',
        'ship_package_id',
        'category_name',
        'journey_type',
        'quantity',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(SaleDraft::class, 'sale_draft_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ShipPackage::class, 'ship_package_id');
    }
}
