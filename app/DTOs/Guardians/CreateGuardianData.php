<?php

declare(strict_types=1);

namespace App\DTOs\Guardians;

final readonly class CreateGuardianData
{
    /**
     * @param  array<int, string>  $studentProfileIds
     */
    public function __construct(
        public string $fullName,
        public string $relationshipType,
        public ?string $contactEmail,
        public ?string $contactPhone,
        public array $studentProfileIds,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            relationshipType: $data['relationship_type'],
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            studentProfileIds: $data['student_profile_ids'] ?? [],
        );
    }
}
