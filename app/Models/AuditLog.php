<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'user_id', 'user_name',
        'event', 'old_values', 'new_values', 'context', 'description', 'sale_id',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
            'sale_id' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::updating(function (AuditLog $log): never {
            throw new LogicException('Audit history cannot be edited.');
        });

        static::deleting(function (AuditLog $log): never {
            throw new LogicException('Audit history cannot be deleted.');
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
