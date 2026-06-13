<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentResponseAttempt;
use App\Models\User;

final class AssessmentResponseVisibilityService
{
    /**
     * @return list<string>
     */
    public function reportSafeFields(): array
    {
        return [
            'assessment_response_count',
            'assessment_completion_status',
            'assessment_grading_status',
            'assessment_score_summary',
        ];
    }

    public function studentCanView(User $actor, AssessmentResponseAttempt $attempt): bool
    {
        return $actor->school_id === $attempt->school_id
            && $actor->studentProfile?->id === $attempt->student_profile_id;
    }

    public function fileAvailability(?AssessmentFileAttachment $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return match ($file->scan_status) {
            'clean' => 'clean_download_allowed',
            'failed' => 'scan_failed',
            default => 'scan_pending',
        };
    }
}
