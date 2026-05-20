<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'requested_by_user_id', 'report_type', 'filter_summary', 'output_formats', 'status', 'generated_at', 'output_expires_at', 'outputs_available'])]
final class ReportRun extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (ReportRun $reportRun): void {
            $reportRun->uuid ??= (string) Str::uuid();
            $reportRun->status ??= 'requested';
            $reportRun->output_formats ??= ['pdf', 'csv'];
            $reportRun->outputs_available ??= false;
        });
    }

    protected function casts(): array
    {
        return [
            'filter_summary' => 'array',
            'output_formats' => 'array',
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

    public function isGenerated(): bool
    {
        return $this->status === 'generated';
    }
}
