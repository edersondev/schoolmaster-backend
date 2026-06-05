<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'report_definition_id', 'definition_version', 'domain', 'fields', 'filters', 'grouping', 'sorting', 'output_formats', 'runtime_filters'])]
final class ReportDefinitionSnapshot extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (ReportDefinitionSnapshot $snapshot): void {
            $snapshot->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'filters' => 'array',
            'grouping' => 'array',
            'sorting' => 'array',
            'output_formats' => 'array',
            'runtime_filters' => 'array',
        ];
    }

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function reportRuns(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }
}
