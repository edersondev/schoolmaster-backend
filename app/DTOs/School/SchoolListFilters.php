<?php

declare(strict_types=1);

namespace App\DTOs\School;

final readonly class SchoolListFilters
{
    public function __construct(
        public ?int $status,
        public ?string $inepCode,
        public ?string $document,
        public ?string $name,
        public ?string $email,
        public ?string $city,
        public ?string $state,
        public ?int $administrativeTypeId,
        public ?int $legalNatureId,
        public ?int $managementTypeId,
        public ?int $pedagogicalApproachId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: self::nullableInt($data['status'] ?? null),
            inepCode: self::nullableString($data['inep_code'] ?? null),
            document: self::nullableString($data['document'] ?? null),
            name: self::nullableString($data['name'] ?? null),
            email: self::nullableString($data['email'] ?? null),
            city: self::nullableString($data['city'] ?? null),
            state: self::nullableString($data['state'] ?? null),
            administrativeTypeId: self::nullableInt($data['administrative_type_id'] ?? null),
            legalNatureId: self::nullableInt($data['legal_nature_id'] ?? null),
            managementTypeId: self::nullableInt($data['management_type_id'] ?? null),
            pedagogicalApproachId: self::nullableInt($data['pedagogical_approach_id'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
