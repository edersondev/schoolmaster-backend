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
    public const TEACHER_WORKFLOW_EVENT_TYPES = [
        'teacher_workflow.lifecycle',
        'teacher_workflow.download',
        'teacher_workflow.correction',
        'teacher_workflow.import',
        'teacher_workflow.validation',
        'teacher_workflow.conflict',
    ];

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

    public function isTeacherWorkflowEvent(): bool
    {
        return in_array($this->event_type, self::TEACHER_WORKFLOW_EVENT_TYPES, true);
    }

    public function targetIdentifier(): ?string
    {
        return $this->affected_resource_type !== null && $this->affected_resource_id !== null
            ? $this->affected_resource_type.':'.$this->affected_resource_id
            : null;
    }
}
