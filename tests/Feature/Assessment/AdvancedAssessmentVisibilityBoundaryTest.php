<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AssessmentResponseAttempt;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedAssessmentVisibilityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_student_cannot_view_response_detail(): void
    {
        $school = School::factory()->create();
        $ownerUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $otherUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $owner = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
        StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $otherUser->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $learningSet = LearningSet::query()->create(['school_id' => $school->id, 'owner_user_id' => $ownerUser->id, 'academic_period_id' => $period->id, 'title' => 'Set']);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $ownerUser->id, 'title' => 'Quiz']);
        $attempt = AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $owner->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);

        $this->withHeaders($this->headers($otherUser, $school))
            ->getJson("/api/v1/student/questionnaire-responses/{$attempt->uuid}")
            ->assertNotFound();
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
