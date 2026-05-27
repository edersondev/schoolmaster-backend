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
    'account_identifier_hash',
    'request_ip_hash',
    'token_hash',
    'status',
    'expires_at',
    'completed_at',
    'superseded_at',
    'request_count',
    'request_window_started_at',
    'failed_completion_count',
    'failed_completion_window_started_at',
    'suppressed_until',
    'delivery_requested_at',
    'delivery_channel',
    'email_delivery_metadata_summary',
])]
#[Hidden(['id', 'account_identifier_hash', 'request_ip_hash', 'token_hash'])]
final class PasswordResetRequest extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (PasswordResetRequest $request): void {
            $request->uuid ??= (string) Str::uuid();
            $request->status ??= 'pending';
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
            'request_window_started_at' => 'immutable_datetime',
            'failed_completion_window_started_at' => 'immutable_datetime',
            'suppressed_until' => 'immutable_datetime',
            'delivery_requested_at' => 'immutable_datetime',
            'email_delivery_metadata_summary' => 'array',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
