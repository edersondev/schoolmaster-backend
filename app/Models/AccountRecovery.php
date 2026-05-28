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
    'account_lock_id',
    'actor_user_id',
    'recovery_type',
    'from_state',
    'to_state',
    'reason',
    'completed_at',
])]
final class AccountRecovery extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AccountRecovery $recovery): void {
            $recovery->uuid ??= (string) Str::uuid();
            $recovery->completed_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
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

    public function accountLock(): BelongsTo
    {
        return $this->belongsTo(AccountLock::class);
    }
}
