<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AuditEventData;
use App\Models\AuditEvent;
use Illuminate\Support\Arr;

final class AuditEventService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'bearer_token',
        'authorization',
    ];

    public function record(AuditEventData $data): AuditEvent
    {
        return AuditEvent::query()->create([
            'event_type' => $data->eventType,
            'actor_user_id' => $data->actorUserId,
            'school_id' => $data->schoolId,
            'affected_resource_type' => $data->affectedResourceType,
            'affected_resource_id' => $data->affectedResourceId,
            'outcome' => $data->outcome,
            'source_ip' => $data->sourceIp,
            'tenant_safe_metadata' => $this->sanitize($data->metadata),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function sanitize(array $metadata): array
    {
        return Arr::where($metadata, fn (mixed $value, string $key): bool => ! in_array(strtolower($key), self::SENSITIVE_KEYS, true));
    }
}
