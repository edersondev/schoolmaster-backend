<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;

final class StudentProfileResource extends StudentProfileSummaryResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'current_academic_year_id' => $this->currentAcademicYear?->uuid,
            'guardian_associations' => $this->resource->relationLoaded('guardians')
                ? StudentGuardianAssociationResource::collection($this->guardians)->resolve()
                : [],
            'enrollment_history' => $this->resource->relationLoaded('enrollmentHistories')
                ? EnrollmentHistoryResource::collection($this->enrollmentHistories)->resolve()
                : [],
        ];
    }
}
