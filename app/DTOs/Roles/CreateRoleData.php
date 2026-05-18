<?php

declare(strict_types=1);

namespace App\DTOs\Roles;

final readonly class CreateRoleData
{
    /**
     * @param  array<int, string>  $permissionIds
     */
    public function __construct(
        public string $scope,
        public string $name,
        public array $permissionIds,
        public ?string $schoolId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            scope: $data['scope'],
            name: $data['name'],
            permissionIds: $data['permission_ids'],
            schoolId: $data['school_id'] ?? null,
        );
    }
}
