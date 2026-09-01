<?php

declare(strict_types=1);

namespace App\DTOs\StudentProfiles;

final readonly class CreateStudentProfileData
{
    /**
     * @param  array<int, StudentProfileGuardianEntryData|array<string, mixed>>  $guardianAssociations
     */
    public function __construct(
        public ?string $userId,
        public string $registrationNumber,
        public string $firstName,
        public string $lastName,
        public ?string $dateOfBirth,
        public ?string $contactEmail,
        public ?string $contactPhone,
        public ?string $currentAcademicYearId,
        public string $status,
        public string $enrolledAt,
        public array $guardianAssociations,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            registrationNumber: $data['registration_number'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            dateOfBirth: $data['date_of_birth'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            currentAcademicYearId: $data['current_academic_year_id'] ?? null,
            status: $data['status'] ?? 'active',
            enrolledAt: $data['enrolled_at'],
            guardianAssociations: array_map(
                static fn (array $association): StudentProfileGuardianEntryData => StudentProfileGuardianEntryData::fromArray($association),
                $data['guardian_associations'] ?? [],
            ),
        );
    }

    /**
     * @return array<int, StudentProfileGuardianEntryData>
     */
    public function guardianEntries(): array
    {
        return array_map(
            static fn (StudentProfileGuardianEntryData|array $association): StudentProfileGuardianEntryData => $association instanceof StudentProfileGuardianEntryData
                ? $association
                : StudentProfileGuardianEntryData::fromArray($association),
            $this->guardianAssociations,
        );
    }
}
