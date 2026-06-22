<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AssessmentResponseAttempt;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\ReportRun;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Reports\ReportOutputGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedAssessmentReportOutputTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_advanced_assessment_report_projection_is_aggregate_only(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.request', 'reports.view']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => User::factory()->create(['school_id' => $school->id])->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $learningSet = LearningSet::query()->create(['school_id' => $school->id, 'owner_user_id' => $admin->id, 'academic_period_id' => $period->id, 'title' => 'Set']);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $admin->id, 'title' => 'Quiz']);
        AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);
        $run = ReportRun::query()->create(['school_id' => $school->id, 'requested_by_user_id' => $admin->id, 'report_type' => 'advanced_assessments', 'filter_summary' => [], 'output_formats' => ['pdf'], 'status' => 'requested', 'generation_status' => 'requested', 'outputs_available' => false]);

        $contents = app(ReportOutputGenerationService::class)->contentsFor($run, 'pdf');

        $this->assertStringContainsString('response_count=1', $contents);
        $this->assertStringNotContainsString('answer_text', $contents);
        $this->assertStringNotContainsString('storage_path', $contents);
        $this->assertStringNotContainsString('private_grading_note', $contents);
    }
}
