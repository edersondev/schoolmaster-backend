<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\Models\LifecycleHistory;

final readonly class AdministrationLifecycleResult
{
    public function __construct(
        public string $resource_type,
        public string $resource_uuid,
        public string $action,
        public string $status,
        public LifecycleHistory $history,
    ) {}
}
