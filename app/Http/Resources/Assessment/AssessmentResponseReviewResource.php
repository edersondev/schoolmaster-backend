<?php

declare(strict_types=1);

namespace App\Http\Resources\Assessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AssessmentResponseReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['school', 'studentProfile', 'questionnaire', 'learningSet', 'academicPeriod', 'answers.question', 'answers.fileAttachment', 'gradingOutcomes.answer', 'gradingOutcomes.grader']);

        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'student_profile_id' => $this->studentProfile?->uuid,
            'questionnaire_id' => $this->questionnaire?->uuid,
            'learning_set_id' => $this->learningSet?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'submission_state' => $this->submission_state,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'grading_status' => $this->grading_status,
            'score_summary' => $this->scoreSummary(),
            'feedback_summary' => null,
            'answers' => $this->answers->map(fn ($answer): array => [
                'id' => $answer->uuid,
                'question_id' => $answer->question?->uuid,
                'question_type' => $answer->question_type,
                'answer_text' => $answer->fileAttachment === null ? $answer->answer_text : null,
                'file' => $answer->fileAttachment === null ? null : [
                    'id' => $answer->fileAttachment->uuid,
                    'filename' => $answer->fileAttachment->sanitized_filename,
                    'declared_content_type' => $answer->fileAttachment->declared_content_type,
                    'detected_content_type' => $answer->fileAttachment->detected_content_type,
                    'file_category' => $answer->fileAttachment->file_category,
                    'file_size_bytes' => $answer->fileAttachment->file_size_bytes,
                    'scan_status' => $answer->fileAttachment->scan_status,
                    'availability' => $answer->fileAttachment->availability_state,
                ],
                'grading_status' => $answer->grading_status,
                'visibility_state' => $answer->visibility_state,
            ])->values()->all(),
            'grading_outcomes' => AssessmentGradingResource::collection($this->gradingOutcomes)->resolve(),
            'file_summary' => [
                'pending_count' => $this->answers->filter(fn ($answer): bool => $answer->fileAttachment?->scan_status === 'pending')->count(),
                'clean_count' => $this->answers->filter(fn ($answer): bool => $answer->fileAttachment?->scan_status === 'clean')->count(),
                'failed_count' => $this->answers->filter(fn ($answer): bool => $answer->fileAttachment?->scan_status === 'failed')->count(),
            ],
        ];
    }

    private function scoreSummary(): ?array
    {
        if ($this->possible_points === null) {
            return null;
        }

        $earned = (float) $this->earned_points;
        $possible = (float) $this->possible_points;

        return [
            'earned_points' => $earned,
            'possible_points' => $possible,
            'percentage' => $possible > 0.0 ? round(($earned / $possible) * 100, 2) : null,
        ];
    }
}
