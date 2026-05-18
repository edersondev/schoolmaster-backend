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

#[Fillable(['uuid', 'school_id', 'owner_user_id', 'academic_period_id', 'title', 'published_at', 'status'])]
final class LearningSet extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (LearningSet $learningSet): void {
            $learningSet->uuid ??= (string) Str::uuid();
            $learningSet->status ??= 'published';
            $learningSet->published_at ??= now();
        });
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LearningSetEntry::class)->orderBy('sequence');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LearningSetAssignment::class);
    }
}
