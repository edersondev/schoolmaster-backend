<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $eventType,
        public string $outcome,
        public ?int $actorUserId = null,
        public ?int $schoolId = null,
        public ?string $affectedResourceType = null,
        public ?string $affectedResourceId = null,
        public ?string $sourceIp = null,
        public array $metadata = [],
    ) {}
}
