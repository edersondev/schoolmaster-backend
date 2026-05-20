<?php

declare(strict_types=1);

namespace App\Services\LearningSets;

use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class LearningSetAssignmentValidator
{
    /**
     * @param  array<int, string>  $studentProfileUuids
     * @return Collection<int, StudentProfile>
     */
    public function validate(array $studentProfileUuids, int $schoolId): Collection
    {
        $profiles = StudentProfile::query()
            ->whereIn('uuid', $studentProfileUuids)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->get();

        if ($profiles->count() !== count(array_unique($studentProfileUuids))) {
            throw ValidationException::withMessages([
                'student_profile_ids' => ['All selected student profiles must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $profiles;
    }
}
