<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\Models\User;

final class EmailDeliveryRequestMetadataService
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function summarize(User $target, array $metadata = []): array
    {
        return array_filter([
            'requested_for_email_hash' => hash('sha256', strtolower($target->email)),
            'requested_at' => now()->toIso8601String(),
            'purpose' => $metadata['purpose'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
