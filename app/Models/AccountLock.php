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
    'user_id',
    'school_id',
    'actor_user_id',
    'lock_type',
    'status',
    'reason',
    'locked_at',
    'cleared_at',
])]
final class AccountLock extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AccountLock $lock): void {
            $lock->uuid ??= (string) Str::uuid();
            $lock->status ??= 'active';
            $lock->locked_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'locked_at' => 'immutable_datetime',
            'cleared_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->cleared_at === null;
    }
}
