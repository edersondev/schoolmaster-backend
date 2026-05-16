<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

final class TenantContextResolver
{
    public function resolve(Request $request, User $user): TenantContext
    {
        $headerSchoolId = $request->header('X-School-Id');

        if ($user->school !== null) {
            if ($user->school->status !== 'active') {
                throw new TenantContextException('Tenant context is inactive.');
            }

            if ($headerSchoolId !== null && $headerSchoolId !== $user->school->uuid) {
                throw new TenantContextException('Tenant context is outside permitted scope.');
            }

            return new TenantContext($user->school, 'authenticated_session', 'resolved');
        }

        if ($headerSchoolId === null) {
            return new TenantContext(null, 'platform', 'missing');
        }

        $school = School::query()->where('uuid', $headerSchoolId)->first();

        if ($school === null || $school->status !== 'active') {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        return new TenantContext($school, 'x-school-id', 'resolved');
    }
}
