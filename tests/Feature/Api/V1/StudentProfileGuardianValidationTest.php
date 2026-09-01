<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileGuardianValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_entries_require_guardian_management_authority(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    ['relationship_type' => 'father', 'full_name' => 'Carlos Costa'],
                ],
            ]))
            ->assertForbidden();
    }

    public function test_guardian_entries_are_limited_to_two(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    ['relationship_type' => 'father', 'full_name' => 'Carlos Costa'],
                    ['relationship_type' => 'mother', 'full_name' => 'Maria Costa'],
                    ['relationship_type' => 'aunt', 'full_name' => 'Ana Costa'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_guardian_entry_requires_exactly_one_mode(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);
        $guardian = Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => 'Existing Guardian',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    ['relationship_type' => 'father', 'guardian_id' => $guardian->uuid, 'full_name' => 'Carlos Costa'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'registration_number' => 'STU-GV-002',
                'guardian_associations' => [
                    ['relationship_type' => 'father'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_existing_guardian_must_be_active_and_same_school(): void
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
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    ['guardian_id' => $otherGuardian->uuid, 'relationship_type' => 'parent'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_duplicate_existing_guardian_reference_is_rejected(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);
        $guardian = Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => 'Existing Guardian',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'father'],
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'mother'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_duplicate_new_guardian_identity_comparison_is_utf8_case_insensitive(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $this->payload([
                'guardian_associations' => [
                    [
                        'relationship_type' => 'father',
                        'full_name' => 'Álvaro Costa',
                        'contact_email' => 'alvaro@example.test',
                    ],
                    [
                        'relationship_type' => 'father',
                        'full_name' => 'álvaro costa',
                        'contact_email' => 'alvaro@example.test',
                    ],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseMissing('guardians', [
            'school_id' => $school->id,
            'contact_email' => 'alvaro@example.test',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'registration_number' => 'STU-GV-001',
            'first_name' => 'Davi',
            'last_name' => 'Lima',
            'enrolled_at' => '2026-02-01',
        ], $overrides);
    }
}
