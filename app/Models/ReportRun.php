<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Reports\ReportGenerationStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'requested_by_user_id', 'report_type', 'filter_summary', 'output_formats', 'status', 'generation_status', 'generated_at', 'output_expires_at', 'outputs_available', 'source_report_run_id', 'superseded_by_report_run_id', 'report_definition_id', 'report_definition_snapshot_id', 'failure_reason_code', 'cancellation_reason_code', 'correlation_id'])]
final class ReportRun extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (ReportRun $reportRun): void {
            $reportRun->uuid ??= (string) Str::uuid();
            $reportRun->status ??= 'requested';
            $reportRun->generation_status ??= $reportRun->status ?? ReportGenerationStatus::Requested->value;
            $reportRun->output_formats ??= ['pdf', 'csv'];
            $reportRun->outputs_available ??= false;
            $reportRun->correlation_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'filter_summary' => 'array',
            'output_formats' => 'array',
            'generation_status' => ReportGenerationStatus::class,
            'generated_at' => 'datetime',
            'output_expires_at' => 'datetime',
            'outputs_available' => 'boolean',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ReportOutput::class);
    }

    public function sourceReportRun(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_report_run_id');
    }

    public function supersededByReportRun(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_report_run_id');
    }

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function reportDefinitionSnapshot(): BelongsTo
    {
        return $this->belongsTo(ReportDefinitionSnapshot::class);
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(ReportLifecycleEvent::class);
    }

    public function isGenerated(): bool
    {
        return $this->status === 'generated' || $this->generation_status === ReportGenerationStatus::Generated;
    }
}
