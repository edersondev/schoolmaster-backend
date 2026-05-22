<?php

declare(strict_types=1);

namespace App\DTOs\StudentProfiles;

final readonly class TransferStudentProfileData
{
    public function __construct(
        public string $effectiveAt,
        public string $reason,
        public ?string $destinationSchoolId,
        public ?string $destinationStudentProfileId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            effectiveAt: $data['effective_at'],
            reason: $data['reason'],
            destinationSchoolId: $data['destination_school_id'] ?? null,
            destinationStudentProfileId: $data['destination_student_profile_id'] ?? null,
        );
    }
}
