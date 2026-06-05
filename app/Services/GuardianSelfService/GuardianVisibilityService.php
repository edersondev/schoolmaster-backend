<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianStudentTarget;
use App\Models\Guardian;
use App\Models\StudentProfile;

final class GuardianVisibilityService
{
    /**
     * @return array<string, mixed>
     */
    public function studentSummary(StudentProfile $student, string $relationshipLabel): array
    {
        return [
            'id' => $student->uuid,
            'school_id' => $student->school?->uuid,
            'registration_number' => $student->registration_number,
            'full_name' => $student->fullName(),
            'status' => $student->status,
            'enrolled_at' => $student->enrolled_at?->toDateString(),
            'relationship_label' => $relationshipLabel,
            'current_academic_year_id' => $student->currentAcademicYear?->uuid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentDetail(GuardianStudentTarget $target): array
    {
        $student = $target->student;

        return $this->studentSummary($student, $target->relationshipLabel) + [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'date_of_birth' => $student->date_of_birth?->toDateString(),
            'enrollment_summary' => [
                'status' => $student->status,
                'enrolled_at' => $student->enrolled_at?->toDateString(),
                'status_effective_at' => $student->status_effective_at?->toDateString(),
                'current_academic_year_id' => $student->currentAcademicYear?->uuid,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function guardianContact(Guardian $guardian): array
    {
        return [
            'guardian_id' => $guardian->uuid,
            'full_name' => $guardian->full_name,
            'contact_email' => $guardian->contact_email,
            'contact_phone' => $guardian->contact_phone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentPrimaryContact(StudentProfile $student): array
    {
        return [
            'full_name' => $student->fullName(),
            'contact_email' => $student->contact_email,
            'contact_phone' => $student->contact_phone,
        ];
    }
}
