<?php

declare(strict_types=1);

namespace Tests\Unit\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\ClassroomRoster\EffectiveDateValidator;
use App\Services\ClassroomRoster\RosterAuditLogger;
use App\Services\ClassroomRoster\SchoolContextGuard;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ClassroomRosterFoundationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_active_roster_membership_uniqueness_is_enforced_by_the_database(): void
    {
        [$school, $period, $actor, $classSection] = $this->context();
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'registration_number' => 'STU-DB-001',
            'first_name' => 'Database',
            'last_name' => 'Guard',
            'status' => 'active',
            'enrolled_at' => '2026-01-01',
        ]);

        RosterMembership::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $actor->id,
        ]);

        $this->expectException(QueryException::class);

        RosterMembership::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'effective_start_date' => '2026-05-30',
            'created_by_user_id' => $actor->id,
        ]);
    }

    public function test_active_teacher_assignment_uniqueness_is_enforced_by_the_database(): void
    {
        [$school, $period, $actor, $classSection] = $this->context();
        $teacher = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        TeacherAssignment::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);

        $this->expectException(QueryException::class);

        TeacherAssignment::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'effective_start_date' => '2026-05-30',
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }

    private function context(): array
    {
        $school = School::factory()->create(['status' => 'active']);
        $actor = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $classSection = ClassSection::query()->create([
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'code' => 'UNIT-001',
            'name' => 'Unit Test Section',
            'status' => 'active',
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);

        return [$school, $period, $actor, $classSection];
    }
}
