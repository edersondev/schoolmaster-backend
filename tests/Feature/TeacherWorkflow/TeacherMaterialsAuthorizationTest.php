<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\School;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherMaterialsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_school_admin_can_access_teacher_materials(): void
    {
        $school = School::factory()->create();
        $owner = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $owner);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $owner);

        $this->withHeaders($this->headers($owner, $school))->getJson("/api/v1/teacher-content/{$content->uuid}")->assertOk();
        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", ['title' => 'Admin Edit'])->assertOk();
    }

    public function test_same_school_non_owner_student_guardian_and_platform_users_are_denied(): void
    {
        $school = School::factory()->create();
        $owner = $this->createTeacher($school);
        $nonOwner = $this->createTeacher($school);
        $student = User::factory()->create(['school_id' => $school->id]);
        $guardian = User::factory()->create(['school_id' => $school->id]);
        $platform = $this->createPlatformUser();
        $content = TeacherWorkflowFactory::cleanContent($school, $owner);

        $this->withHeaders($this->headers($nonOwner, $school))->getJson("/api/v1/teacher-content/{$content->uuid}")->assertForbidden();
        $this->withHeaders($this->headers($student, $school))->getJson("/api/v1/teacher-content/{$content->uuid}")->assertForbidden();
        $this->withHeaders($this->headers($guardian, $school))->getJson("/api/v1/teacher-content/{$content->uuid}")->assertForbidden();

        $this->withToken($this->bearerTokenFor($platform))
            ->getJson("/api/v1/teacher-content/{$content->uuid}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_cross_tenant_materials_are_not_found_in_resolved_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $otherContent = TeacherWorkflowFactory::cleanContent($otherSchool, $this->createTeacher($otherSchool));

        $this->withHeaders($this->headers($teacher, $school))
            ->getJson("/api/v1/teacher-content/{$otherContent->uuid}")
            ->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function headers(User $user, School $school): array
    {
        return [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($user),
            'X-School-Id' => $school->uuid,
        ];
    }
}
