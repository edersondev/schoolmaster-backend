<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\School;

final class TenantContextService
{
    public function requireSchool(TenantContext $context): School
    {
        if (! $context->isResolved() || $context->school === null) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        return $context->school;
    }
}
