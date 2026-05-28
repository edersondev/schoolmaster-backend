<?php

declare(strict_types=1);

namespace App\DTOs\AccountLifecycle;

final readonly class CreateAccountInvitationData
{
    /**
     * @param  array<int, string>  $roleIds
     * @param  array<string, mixed>  $deliveryMetadata
     */
    public function __construct(
        public string $scope,
        public string $fullName,
        public string $email,
        public array $roleIds,
        public ?string $schoolId = null,
        public array $deliveryMetadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            scope: $data['scope'],
            fullName: $data['full_name'],
            email: strtolower($data['email']),
            roleIds: $data['role_ids'],
            schoolId: $data['school_id'] ?? null,
            deliveryMetadata: $data['delivery_metadata'] ?? [],
        );
    }
}
