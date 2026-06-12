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
    'actor_user_id',
    'school_id',
    'support_access_decision_id',
    'target_school_support_opt_in_id',
    'internal_platform_approval_id',
    'action',
    'outcome',
    'target_type',
    'target_id',
    'correlation_id',
    'reason_code',
    'metadata',
    'occurred_at',
])]
#[Hidden(['id', 'actor_user_id', 'school_id', 'support_access_decision_id', 'target_school_support_opt_in_id', 'internal_platform_approval_id'])]
final class PlatformSupportAuditEvent extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (PlatformSupportAuditEvent $event): void {
            $event->uuid ??= (string) Str::uuid();
            $event->correlation_id ??= (string) Str::uuid();
            $event->occurred_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
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

    public function supportAccessDecision(): BelongsTo
    {
        return $this->belongsTo(SupportAccessDecision::class);
    }

    public function targetSchoolSupportOptIn(): BelongsTo
    {
        return $this->belongsTo(TargetSchoolSupportOptIn::class);
    }

    public function internalPlatformApproval(): BelongsTo
    {
        return $this->belongsTo(InternalPlatformApproval::class);
    }
}
