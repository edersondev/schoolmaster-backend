<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Models\AssessmentResponseAttempt;

final class AssessmentResponseStateService
{
    public function refreshFromAnswers(AssessmentResponseAttempt $attempt): AssessmentResponseAttempt
    {
        $attempt->load('answers.fileAttachment', 'gradingOutcomes');

        $hasPendingFile = $attempt->answers->contains(fn ($answer): bool => $answer->fileAttachment?->scan_status === 'pending');
        $hasFailedFile = $attempt->answers->contains(fn ($answer): bool => $answer->fileAttachment?->scan_status === 'failed');
        $latestOutcomes = $attempt->gradingOutcomes
            ->whereNotNull('assessment_answer_id')
            ->sortByDesc('id')
            ->unique('assessment_answer_id');
        $gradedCount = $latestOutcomes->whereIn('grading_status', ['graded', 'exempted'])->count();
        $answerCount = $attempt->answers->count();

        $state = match (true) {
            $hasPendingFile => 'scan_blocked',
            $hasFailedFile => 'scan_failed',
            $answerCount > 0 && $gradedCount >= $answerCount => 'graded',
            $gradedCount > 0 => 'partially_graded',
            default => 'needs_review',
        };

        $attempt->forceFill([
            'submission_state' => $state,
            'grading_status' => $state,
        ])->save();

        return $attempt;
    }
}
