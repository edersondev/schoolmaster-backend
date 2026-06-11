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
    'actor_user_id',
    'school_id',
    'target_school_support_opt_in_id',
    'internal_platform_approval_id',
    'reason_code',
    'purpose',
    'correlation_id',
    'state',
    'support_opt_in_state',
    'platform_approval_state',
    'approved_at',
    'expires_at',
    'revoked_at',
    'revocation_reason_code',
])]
#[Hidden(['id', 'actor_user_id', 'school_id', 'target_school_support_opt_in_id', 'internal_platform_approval_id'])]
final class SupportAccessDecision extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (SupportAccessDecision $decision): void {
            $decision->uuid ??= (string) Str::uuid();
            $decision->state ??= 'requested';
            $decision->support_opt_in_state ??= 'pending';
            $decision->platform_approval_state ??= 'pending';
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function targetSchoolSupportOptIn(): BelongsTo
    {
        return $this->belongsTo(TargetSchoolSupportOptIn::class);
    }

    public function internalPlatformApproval(): BelongsTo
    {
        return $this->belongsTo(InternalPlatformApproval::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(PlatformSupportAuditEvent::class);
    }
}
