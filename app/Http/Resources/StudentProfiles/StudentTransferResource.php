<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $transfer = $this->resource['transfer'];

        return [
            'student_profile' => (new StudentProfileResource($this->resource['student_profile']))->resolve(),
            'transfer' => [
                'id' => $transfer->uuid,
                'school_id' => $transfer->school?->uuid,
                'student_profile_id' => $transfer->studentProfile?->uuid,
                'destination_school_id' => $transfer->destinationSchool?->uuid,
                'destination_student_profile_id' => $transfer->destinationStudentProfile?->uuid,
                'effective_at' => $transfer->effective_at?->toDateString(),
                'reason' => $transfer->reason,
                'actor_user_id' => $transfer->actor?->uuid,
            ],
            'enrollment_history' => (new EnrollmentHistoryResource($this->resource['enrollment_history']))->resolve(),
        ];
    }
}
