<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\TenantContextException;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;

trait AssertsAdministrationTenantScope
{
    private function assertAdministrationSchoolScope(Model $resource, School $school): void
    {
        if ((int) $resource->getAttribute('school_id') !== $school->id) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }
    }
}
