<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentAcademicRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_academic_record_endpoints_reject_missing_profile(): void
    {
        $school = School::factory()->create();
        $studentUser = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
