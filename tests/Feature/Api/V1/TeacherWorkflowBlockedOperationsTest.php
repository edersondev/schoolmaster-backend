<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowBlockedOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_undocumented_teacher_workflow_operations_are_not_registered(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $token = $this->bearerTokenFor($teacher);

        foreach ([
            ['getJson', '/api/v1/teacher-content-folders'],
            ['postJson', '/api/v1/teacher-content/'.fake()->uuid().'/publish'],
            ['postJson', '/api/v1/teacher-content/'.fake()->uuid().'/archive'],
            ['postJson', '/api/v1/questionnaires/'.fake()->uuid().'/duplicate'],
            ['getJson', '/api/v1/classrooms'],
        ] as [$method, $path]) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->{$method}($path)
                ->assertNotFound();
        }
    }
}
