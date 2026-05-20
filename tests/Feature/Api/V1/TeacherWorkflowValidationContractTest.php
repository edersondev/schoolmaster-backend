<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowValidationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_reject_unsupported_filters(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        foreach (['teacher-content', 'questionnaires', 'learning-sets', 'grades', 'attendance'] as $path) {
            $this->withToken($this->bearerTokenFor($teacher))
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson('/api/v1/'.$path.'?status=active')
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'validation_failed');
        }
    }

    public function test_create_requests_reject_undocumented_fields(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        foreach ([
            'questionnaires' => ['title' => 'Quiz', 'questions' => [], 'undocumented' => true],
            'learning-sets' => ['academic_period_id' => fake()->uuid(), 'title' => 'Set', 'entries' => [], 'student_profile_ids' => [], 'undocumented' => true],
            'grades' => ['student_profile_id' => fake()->uuid(), 'academic_period_id' => fake()->uuid(), 'grade_value' => 50, 'undocumented' => true],
            'attendance' => ['student_profile_id' => fake()->uuid(), 'academic_period_id' => fake()->uuid(), 'attendance_date' => '2026-01-01', 'attendance_status' => 'present', 'undocumented' => true],
        ] as $path => $payload) {
            $this->withToken($this->bearerTokenFor($teacher))
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson('/api/v1/'.$path, $payload)
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'validation_failed');
        }
    }
}
