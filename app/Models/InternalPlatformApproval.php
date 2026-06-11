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
    'support_access_decision_id',
    'approver_user_id',
    'support_actor_user_id',
    'school_id',
    'state',
    'reason_code',
    'correlation_id',
    'approved_at',
    'expires_at',
    'revoked_at',
    'revocation_reason_code',
])]
#[Hidden(['id', 'support_access_decision_id', 'approver_user_id', 'support_actor_user_id', 'school_id'])]
final class InternalPlatformApproval extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (InternalPlatformApproval $approval): void {
            $approval->uuid ??= (string) Str::uuid();
            $approval->state ??= 'approved';
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

    public function supportAccessDecision(): BelongsTo
    {
        return $this->belongsTo(SupportAccessDecision::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function supportActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'support_actor_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(PlatformSupportAuditEvent::class);
    }
}
