<?php

declare(strict_types=1);

namespace App\DTOs\AdministrationLifecycle;

final readonly class ApplyBulkLifecycleActionData
{
    /**
     * @param  array<int, string>  $recordIds
     */
    public function __construct(
        public string $resourceType,
        public string $action,
        public array $recordIds,
        public string $effectiveAt,
        public string $reason,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            resourceType: (string) $data['resource_type'],
            action: (string) $data['action'],
            recordIds: array_values($data['record_ids'] ?? []),
            effectiveAt: (string) $data['effective_at'],
            reason: (string) $data['reason'],
        );
    }
}
