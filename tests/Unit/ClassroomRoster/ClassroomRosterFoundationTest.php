<?php

declare(strict_types=1);

namespace Tests\Unit\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\AcademicPeriod;
use App\Models\School;
use App\Services\ClassroomRoster\EffectiveDateValidator;
use App\Services\ClassroomRoster\RosterAuditLogger;
use App\Services\ClassroomRoster\SchoolContextGuard;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ClassroomRosterFoundationTest extends TestCase
{
    public function test_school_context_guard_rejects_unresolved_context_before_lookup(): void
    {
        $this->expectException(TenantContextException::class);

        app(SchoolContextGuard::class)->requireResolved(new TenantContext(null, 'platform', 'missing'));
    }

    public function test_effective_date_uses_application_timezone_when_school_timezone_is_missing(): void
    {
        config(['app.timezone' => 'America/Sao_Paulo']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-30 00:30:00', 'America/Sao_Paulo'));

        $school = (new School)->forceFill(['status' => 'active']);
        $period = (new AcademicPeriod)->forceFill([
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);

        app(EffectiveDateValidator::class)->assertUsable(new EffectiveDateInput(
            school: $school,
            academicPeriod: $period,
            effectiveDate: CarbonImmutable::parse('2026-05-30')->startOfDay(),
            field: 'effective_start_date',
        ));

        CarbonImmutable::setTestNow();
        $this->assertTrue(true);
    }

    public function test_effective_date_must_fall_inside_academic_period(): void
    {
        $this->expectException(ValidationException::class);

        $school = (new School)->forceFill(['status' => 'active', 'timezone' => 'UTC']);
        $period = (new AcademicPeriod)->forceFill([
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);

        app(EffectiveDateValidator::class)->assertUsable(new EffectiveDateInput(
            school: $school,
            academicPeriod: $period,
            effectiveDate: CarbonImmutable::parse('2026-04-01')->startOfDay(),
            field: 'effective_start_date',
        ));
    }

    public function test_lifecycle_ending_date_cannot_precede_effective_start_date(): void
    {
        $this->expectException(ValidationException::class);

        app(EffectiveDateValidator::class)->assertEndingDateNotBeforeStart(
            endingDate: CarbonImmutable::parse('2026-02-01'),
            startDate: CarbonImmutable::parse('2026-02-02'),
            field: 'effective_end_date',
        );
    }

    public function test_roster_audit_metadata_is_minimized_to_tenant_safe_fields(): void
    {
        $logger = app(RosterAuditLogger::class);

        $metadata = $logger->tenantSafeMetadata([
            'academic_period_id' => 'period-uuid',
            'batch_size' => 2,
            'student_name' => 'Private Student',
            'teacher_email' => 'private@example.com',
            'password' => 'secret',
            'conflict_type' => 'duplicate_code',
        ]);

        $this->assertSame([
            'academic_period_id' => 'period-uuid',
            'batch_size' => 2,
            'conflict_type' => 'duplicate_code',
        ], $metadata);
    }
}
