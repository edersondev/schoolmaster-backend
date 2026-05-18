<?php

declare(strict_types=1);

namespace App\DTOs\Users;

final readonly class CreateUserData
{
    /**
     * @param  array<int, string>  $roleIds
     */
    public function __construct(
        public string $fullName,
        public string $email,
        public array $roleIds,
        public ?string $schoolId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            email: $data['email'],
            roleIds: $data['role_ids'],
            schoolId: $data['school_id'] ?? null,
        );
    }
}
