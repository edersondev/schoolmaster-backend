<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class GuardianAssociationValidator
{
    /**
     * @param  array<int, array{guardian_id: string, relationship_type: string}>  $associations
     * @return Collection<int, Guardian>
     */
    public function activeSameSchoolGuardians(array $associations, School $school): Collection
    {
        if ($associations === []) {
            return new Collection;
        }

        $guardianIds = array_column($associations, 'guardian_id');

        if (count($guardianIds) !== count(array_unique($guardianIds))) {
            throw ValidationException::withMessages([
                'guardian_associations' => ['Guardian references must be unique.'],
            ]);
        }

        $guardians = Guardian::query()
            ->whereIn('uuid', $guardianIds)
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        if ($guardians->count() !== count($guardianIds)) {
            throw ValidationException::withMessages([
                'guardian_associations' => ['All guardians must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $guardians;
    }
}
