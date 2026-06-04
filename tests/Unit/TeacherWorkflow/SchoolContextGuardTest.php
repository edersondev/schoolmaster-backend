<?php

declare(strict_types=1);

namespace Tests\Unit\TeacherWorkflow;

use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\School;
use App\Services\TeacherWorkflow\SchoolContextGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolContextGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_require_resolved_fails_before_lookup_for_missing_context(): void
    {
        $guard = new SchoolContextGuard;
        $context = new TenantContext(null, 'header', 'missing');

        $this->expectException(TenantContextException::class);
        $this->expectExceptionMessage('Tenant context is missing, inactive, or outside permitted scope.');

        $guard->requireResolved($context);
    }

    public function test_require_resolved_rejects_inactive_school_context(): void
    {
        $school = School::factory()->create(['status' => 'inactive']);
        $guard = new SchoolContextGuard;
        $context = new TenantContext($school, 'header', 'resolved');

        $this->expectException(TenantContextException::class);
        $this->expectExceptionMessage('Tenant context is inactive.');

        $guard->requireResolved($context);
    }
}
