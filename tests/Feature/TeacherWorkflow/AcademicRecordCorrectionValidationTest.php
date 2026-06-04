<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordCorrectionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_correction_validation_and_immutable_fields(): void
    {
        [$school, $teacher, $student, $period] = $this->context();
        $grade = GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'original_recorded_by_user_id' => $teacher->id, 'grade_value' => 80, 'status' => 'active', 'recorded_at' => now()]);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90])->assertUnprocessable();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90, 'correction_reason' => 'short'])->assertUnprocessable();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 101, 'correction_reason' => 'Corrected after full review.'])->assertUnprocessable();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90, 'student_profile_id' => $student->uuid, 'correction_reason' => 'Unsupported immutable field.'])->assertUnprocessable();

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90, 'correction_reason' => 'Corrected after full review.'])->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 91, 'correction_reason' => 'Second correction after review.'])->assertOk()->assertJsonCount(2, 'data.correction_history');

        $this->assertDatabaseHas('grade_records', ['id' => $grade->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id]);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
