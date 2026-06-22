<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentActorContext;
use App\Models\AssessmentFileAttachment;
use InvalidArgumentException;

final class AssessmentFileScanOutcomeService
{
    public function markClean(AssessmentActorContext $context, AssessmentFileAttachment $attachment): AssessmentFileAttachment
    {
        return $this->transition($context, $attachment, 'clean', 'clean_download_allowed');
    }

    public function markFailed(AssessmentActorContext $context, AssessmentFileAttachment $attachment): AssessmentFileAttachment
    {
        return $this->transition($context, $attachment, 'failed', 'scan_failed');
    }

    private function transition(
        AssessmentActorContext $context,
        AssessmentFileAttachment $attachment,
        string $scanStatus,
        string $availabilityState,
    ): AssessmentFileAttachment {
        if ($attachment->school_id !== $context->school->id || $attachment->scan_status !== 'pending') {
            throw new InvalidArgumentException('Assessment file scan transition is not allowed.');
        }

        $attachment->forceFill([
            'scan_status' => $scanStatus,
            'availability_state' => $availabilityState,
            'scanned_at' => now(),
        ])->save();

        return $attachment;
    }
}
