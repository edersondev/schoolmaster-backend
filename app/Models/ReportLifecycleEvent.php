<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'actor_user_id', 'report_run_id', 'report_definition_id', 'action', 'outcome', 'target_type', 'target_id', 'correlation_id', 'reason_code', 'summary', 'occurred_at'])]
final class ReportLifecycleEvent extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (ReportLifecycleEvent $event): void {
            $event->uuid ??= (string) Str::uuid();
            $event->correlation_id ??= (string) Str::uuid();
            $event->occurred_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function reportRun(): BelongsTo
    {
        return $this->belongsTo(ReportRun::class);
    }

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }
}
