<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileCreateAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_guardian_entry_rolls_back_student_new_guardian_and_association(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);
        $otherGuardian = Guardian::query()->create([
            'school_id' => $otherSchool->id,
            'full_name' => 'Other Guardian',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-GA-001',
                'first_name' => 'Eva',
                'last_name' => 'Pires',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['relationship_type' => 'mother', 'full_name' => 'Maria Pires'],
                    ['guardian_id' => $otherGuardian->uuid, 'relationship_type' => 'father'],
                ],
            ])
            ->assertUnprocessable();

        $this->assertSame(0, StudentProfile::query()->where('registration_number', 'STU-GA-001')->count());
        $this->assertDatabaseMissing('guardians', ['full_name' => 'Maria Pires', 'school_id' => $school->id]);
        $this->assertDatabaseMissing('guardian_student_profile', ['relationship_type' => 'mother']);
    }
}
