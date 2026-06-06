<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportActorContext;
use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Support\Str;

final class ReportTenantContextService
{
    public function __construct(
        private readonly TenantContextService $tenantContext,
    ) {}

    public function resolve(User $actor, TenantContext $context): ReportActorContext
    {
        return new ReportActorContext(
            actor: $actor,
            school: $this->tenantContext->requireSchool($context),
            correlationId: (string) Str::uuid(),
        );
    }
}
