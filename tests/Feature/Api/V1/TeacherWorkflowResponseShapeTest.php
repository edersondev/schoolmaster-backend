<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_use_paginated_envelope_and_unauthenticated_requests_use_error_envelope(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        foreach (['teacher-content', 'questionnaires', 'learning-sets', 'grades', 'attendance'] as $path) {
            $this->withToken($this->bearerTokenFor($teacher))
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson('/api/v1/'.$path)
                ->assertOk()
                ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);

            $this->withHeader('Authorization', '')
                ->getJson('/api/v1/'.$path)
                ->assertUnauthorized()
                ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
        }
    }
}
