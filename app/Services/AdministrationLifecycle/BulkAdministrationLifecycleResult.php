<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

final readonly class BulkAdministrationLifecycleResult
{
    /**
     * @param  array<int, AdministrationLifecycleResult>  $results
     */
    public function __construct(
        public string $resource_type,
        public string $action,
        public array $results,
    ) {}
}
