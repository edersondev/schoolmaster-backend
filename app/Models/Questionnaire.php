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

#[Fillable([
    'uuid',
    'school_id',
    'owner_user_id',
    'title',
    'description',
    'status',
    'deleted_by_user_id',
    'restored_at',
    'restored_by_user_id',
])]
final class Questionnaire extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (Questionnaire $questionnaire): void {
            $questionnaire->uuid ??= (string) Str::uuid();
            $questionnaire->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestion::class)->orderBy('sequence');
    }

    public function learningSetEntries(): HasMany
    {
        return $this->hasMany(LearningSetEntry::class, 'entry_reference_id')
            ->where('entry_type', 'questionnaire');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'affected_resource_id', 'uuid')
            ->where('affected_resource_type', self::class);
    }
}
