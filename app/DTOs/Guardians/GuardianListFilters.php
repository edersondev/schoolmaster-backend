<?php

declare(strict_types=1);

namespace App\DTOs\Guardians;

final readonly class GuardianListFilters
{
    public function __construct(
        public ?string $fullName,
        public ?string $contactEmail,
        public ?string $status,
        public int $perPage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: self::nullableString($data['full_name'] ?? null),
            contactEmail: self::nullableString($data['contact_email'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            perPage: (int) ($data['per_page'] ?? 25),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
