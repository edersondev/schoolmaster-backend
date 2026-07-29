<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MasterAccessSafetyGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_file_safety_identity_and_closed_period_gates_still_apply(): void
    {
        $school = School::factory()->create();
        $master = $this->createSystemAdministrator();
        $headers = [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($master),
            'X-School-Id' => $school->uuid,
        ];
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => 'Safety Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Closed Term',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'closed',
        ]);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'registration_number' => 'SAFETY-STUDENT',
            'status' => 'active',
        ]);
        $pendingContent = TeacherWorkflowFactory::cleanContent(
            $school,
            $this->createTeacher($school),
            ['scan_status' => 'pending'],
        );

        $this->withHeaders($headers)
            ->postJson('/api/v1/academic-years/'.$year->uuid.'/deactivate')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->withHeaders($headers)
            ->postJson('/api/v1/grades/imports', ['rows' => [[
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 88,
            ]]])
            ->assertUnprocessable();

        $this->withHeaders($headers)
            ->getJson('/api/v1/teacher-content/'.$pendingContent->uuid.'/download')
            ->assertForbidden();

        $this->withHeaders($headers)->getJson('/api/v1/student/grades')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/guardian/students')->assertForbidden();
    }
}
