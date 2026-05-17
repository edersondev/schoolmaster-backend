<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_type',
    'actor_user_id',
    'school_id',
    'affected_resource_type',
    'affected_resource_id',
    'outcome',
    'source_ip',
    'tenant_safe_metadata',
    'occurred_at',
])]
final class AuditEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'tenant_safe_metadata' => 'array',
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
}
