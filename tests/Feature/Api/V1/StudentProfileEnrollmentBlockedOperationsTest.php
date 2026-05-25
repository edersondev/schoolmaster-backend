<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentBlockedOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjacent_student_enrollment_operations_remain_unexposed(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage', 'student_transfers.manage']);
        $token = $this->bearerTokenFor($admin);

        foreach ([
            '/api/v1/student-profiles/bulk-import',
            '/api/v1/student-profiles/merge',
            '/api/v1/student-profiles/restore',
            '/api/v1/rosters',
            '/api/v1/classrooms',
            '/api/v1/guardian-self-service/student-profiles',
        ] as $path) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson($path, [])
                ->assertNotFound();
        }
    }
}
