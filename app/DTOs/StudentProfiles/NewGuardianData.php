<?php

declare(strict_types=1);

namespace App\DTOs\StudentProfiles;

final readonly class NewGuardianData
{
    public function __construct(
        public string $fullName,
        public ?string $contactEmail,
        public ?string $contactPhone,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fullName: trim((string) $data['full_name']),
            contactEmail: self::blankToNull($data['contact_email'] ?? null),
            contactPhone: self::blankToNull($data['contact_phone'] ?? null),
        );
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
