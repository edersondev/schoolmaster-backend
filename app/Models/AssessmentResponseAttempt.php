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

#[Fillable([
    'uuid',
    'school_id',
    'student_profile_id',
    'questionnaire_id',
    'learning_set_id',
    'academic_period_id',
    'submission_state',
    'grading_status',
    'earned_points',
    'possible_points',
    'submitted_at',
])]
final class AssessmentResponseAttempt extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AssessmentResponseAttempt $attempt): void {
            $attempt->uuid ??= (string) Str::uuid();
            $attempt->submission_state ??= 'submitted';
            $attempt->grading_status ??= 'needs_review';
            $attempt->submitted_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'earned_points' => 'decimal:2',
            'possible_points' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function learningSet(): BelongsTo
    {
        return $this->belongsTo(LearningSet::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    public function gradingOutcomes(): HasMany
    {
        return $this->hasMany(AssessmentGradingOutcome::class);
    }
}
