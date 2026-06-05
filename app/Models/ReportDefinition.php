<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Reports\ReportDefinitionState;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'created_by_user_id', 'updated_by_user_id', 'name', 'description', 'domain', 'fields', 'filters', 'grouping', 'sorting', 'output_formats', 'lifecycle_state', 'version'])]
final class ReportDefinition extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (ReportDefinition $definition): void {
            $definition->uuid ??= (string) Str::uuid();
            $definition->lifecycle_state ??= ReportDefinitionState::Draft->value;
            $definition->version ??= 1;
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
            'lifecycle_state' => ReportDefinitionState::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ReportDefinitionSnapshot::class);
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(ReportLifecycleEvent::class);
    }

    public function isActive(): bool
    {
        return $this->lifecycle_state === ReportDefinitionState::Active;
    }

    public function isStructurallyEditable(): bool
    {
        return in_array($this->lifecycle_state, [ReportDefinitionState::Draft, ReportDefinitionState::Inactive], true);
    }
}
