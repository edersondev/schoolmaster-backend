<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentReportingHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_reporting_foundation_happy_path(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school, ['reports.request', 'reports.view']);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'content_item', $content->id);
        TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        TeacherWorkflowFactory::attendance($school, $teacher, $period, $student);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/learning-sets?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_type' => 'attendance',
                'filters' => ['academic_period_id' => $period->uuid],
                'output_formats' => ['pdf', 'csv'],
            ])
            ->assertAccepted();
    }
}
