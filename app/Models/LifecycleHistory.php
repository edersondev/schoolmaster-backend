<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'resource_type',
    'resource_id',
    'resource_uuid',
    'operation',
    'from_status',
    'to_status',
    'effective_at',
    'reason',
    'actor_user_id',
    'metadata_summary',
])]
final class LifecycleHistory extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (LifecycleHistory $history): void {
            $history->uuid ??= (string) Str::uuid();
        });

        self::updating(function (): bool {
            return false;
        });

        self::deleting(function (): bool {
            return false;
        });
    }

    protected function casts(): array
    {
        return [
            'effective_at' => 'date',
            'metadata_summary' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
