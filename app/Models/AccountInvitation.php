<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'target_user_id',
    'school_id',
    'actor_user_id',
    'scope',
    'token_hash',
    'status',
    'expires_at',
    'completed_at',
    'superseded_at',
    'revoked_at',
    'send_count',
    'send_window_started_at',
    'failed_completion_count',
    'failed_completion_window_started_at',
    'last_failed_completion_ip',
    'delivery_requested_at',
    'delivery_channel',
    'email_delivery_metadata_summary',
])]
#[Hidden(['id', 'token_hash'])]
final class AccountInvitation extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AccountInvitation $invitation): void {
            $invitation->uuid ??= (string) Str::uuid();
            $invitation->status ??= 'pending';
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'send_window_started_at' => 'immutable_datetime',
            'failed_completion_window_started_at' => 'immutable_datetime',
            'delivery_requested_at' => 'immutable_datetime',
            'email_delivery_metadata_summary' => 'array',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }
}
