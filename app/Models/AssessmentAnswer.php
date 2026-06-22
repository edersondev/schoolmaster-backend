<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'assessment_response_attempt_id',
    'questionnaire_question_id',
    'question_type',
    'answer_text',
    'answer_metadata',
    'validation_status',
    'grading_status',
    'visibility_state',
])]
final class AssessmentAnswer extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AssessmentAnswer $answer): void {
            $answer->uuid ??= (string) Str::uuid();
            $answer->validation_status ??= 'accepted';
            $answer->grading_status ??= 'needs_review';
            $answer->visibility_state ??= 'student_safe';
        });
    }

    protected function casts(): array
    {
        return [
            'answer_metadata' => 'array',
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

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'questionnaire_question_id');
    }

    public function fileAttachment(): HasOne
    {
        return $this->hasOne(AssessmentFileAttachment::class);
    }

    public function gradingOutcomes(): HasMany
    {
        return $this->hasMany(AssessmentGradingOutcome::class);
    }
}
