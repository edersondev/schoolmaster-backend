<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\Models\LifecycleHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class LifecycleHistoryRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Model $resource,
        User $actor,
        string $operation,
        string $effectiveAt,
        string $reason,
        ?string $fromStatus,
        ?string $toStatus,
        array $metadata = [],
    ): LifecycleHistory {
        $schoolId = $resource->getAttribute('school_id');

        return LifecycleHistory::query()->create([
            'school_id' => $schoolId === null ? null : (int) $schoolId,
            'resource_type' => $resource::class,
            'resource_id' => (int) $resource->getKey(),
            'resource_uuid' => (string) $resource->getAttribute('uuid'),
            'operation' => $operation,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'effective_at' => $effectiveAt,
            'reason' => $reason,
            'actor_user_id' => $actor->id,
            'metadata_summary' => $metadata === [] ? null : $metadata,
        ])->load(['school', 'actor']);
    }
}
