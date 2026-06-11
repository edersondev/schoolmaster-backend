<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'requested_by_user_id',
    'approved_by_user_id',
    'state',
    'reason_code',
    'purpose',
    'correlation_id',
    'approved_at',
    'expires_at',
    'revoked_at',
    'revocation_reason_code',
])]
#[Hidden(['id', 'school_id', 'requested_by_user_id', 'approved_by_user_id'])]
final class TargetSchoolSupportOptIn extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (TargetSchoolSupportOptIn $optIn): void {
            $optIn->uuid ??= (string) Str::uuid();
            $optIn->state ??= 'approved';
        });
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function supportAccessDecisions(): HasMany
    {
        return $this->hasMany(SupportAccessDecision::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(PlatformSupportAuditEvent::class);
    }
}
