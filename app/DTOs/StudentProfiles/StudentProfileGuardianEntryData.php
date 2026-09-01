<?php

declare(strict_types=1);

namespace App\DTOs\StudentProfiles;

final readonly class StudentProfileGuardianEntryData
{
    public function __construct(
        public string $relationshipType,
        public ?string $guardianId,
        public ?NewGuardianData $newGuardian,
    ) {}

    public static function fromArray(array $data): self
    {
        $guardianId = self::blankToNull($data['guardian_id'] ?? null);

        return new self(
            relationshipType: trim((string) $data['relationship_type']),
            guardianId: $guardianId,
            newGuardian: $guardianId === null ? NewGuardianData::fromArray($data) : null,
        );
    }

    public function isExistingGuardian(): bool
    {
        return $this->guardianId !== null;
    }

    public function isNewGuardian(): bool
    {
        return $this->newGuardian !== null;
    }

    private static function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
