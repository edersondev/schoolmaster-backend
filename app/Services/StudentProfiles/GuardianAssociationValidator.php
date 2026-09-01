<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\StudentProfiles\StudentProfileGuardianEntryData;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class GuardianAssociationValidator
{
    /**
     * @param  array<int, StudentProfileGuardianEntryData|array<string, mixed>>  $associations
     * @return Collection<int, Guardian>
     */
    public function activeSameSchoolGuardians(array $associations, School $school): Collection
    {
        $entries = $this->normalizeEntries($associations);

        if ($entries === []) {
            return new Collection;
        }

        $guardianIds = array_values(array_filter(
            array_map(static fn (StudentProfileGuardianEntryData $entry): ?string => $entry->guardianId, $entries),
        ));

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

    /**
     * @param  array<int, StudentProfileGuardianEntryData|array<string, mixed>>  $associations
     */
    public function assertUniqueNewGuardianIdentities(array $associations): void
    {
        $seen = [];

        foreach ($this->normalizeEntries($associations) as $entry) {
            if (! $entry->isNewGuardian() || $entry->newGuardian === null) {
                continue;
            }

            $key = strtolower(implode('|', [
                $entry->newGuardian->fullName,
                $entry->newGuardian->contactEmail ?? '',
                $entry->newGuardian->contactPhone ?? '',
            ]));

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'guardian_associations' => ['Duplicate guardian entries are not allowed.'],
                ]);
            }

            $seen[$key] = true;
        }
    }

    /**
     * @param  array<int, StudentProfileGuardianEntryData|array<string, mixed>>  $associations
     * @return array<int, StudentProfileGuardianEntryData>
     */
    private function normalizeEntries(array $associations): array
    {
        return array_map(
            static fn (StudentProfileGuardianEntryData|array $association): StudentProfileGuardianEntryData => $association instanceof StudentProfileGuardianEntryData
                ? $association
                : StudentProfileGuardianEntryData::fromArray($association),
            $associations,
        );
    }
}
