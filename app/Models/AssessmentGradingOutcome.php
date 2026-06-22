<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'assessment_response_attempt_id',
    'assessment_answer_id',
    'grader_user_id',
    'grading_status',
    'score',
    'outcome',
    'feedback_summary',
    'private_grading_note',
    'graded_at',
])]
final class AssessmentGradingOutcome extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AssessmentGradingOutcome $outcome): void {
            $outcome->uuid ??= (string) Str::uuid();
            $outcome->graded_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function responseAttempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentResponseAttempt::class, 'assessment_response_attempt_id');
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(AssessmentAnswer::class, 'assessment_answer_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grader_user_id');
    }
}
