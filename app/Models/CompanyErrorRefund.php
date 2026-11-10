<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyErrorRefund extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyErrorRefundFactory> */
    use HasFactory;

    protected $fillable = [
        'refund_id', 'loss_type', 'loss_percent', 'loss_amount', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return ['loss_percent' => 'decimal:2', 'loss_amount' => 'decimal:2'];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
