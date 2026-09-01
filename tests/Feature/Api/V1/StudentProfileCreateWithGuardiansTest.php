<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileCreateWithGuardiansTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_student_profile_without_guardians(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-G-001',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.guardian_associations', []);
    }

    public function test_school_admin_can_create_student_profile_with_two_new_guardians(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage', 'guardians.manage']);

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-G-002',
                'first_name' => 'Breno',
                'last_name' => 'Costa',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['relationship_type' => 'father', 'full_name' => 'Carlos Costa', 'contact_email' => 'carlos@example.test'],
                    ['relationship_type' => 'mother', 'full_name' => 'Maria Costa', 'contact_phone' => '+5511999999999'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.guardian_associations.0.relationship_type', 'father')
            ->assertJsonPath('data.guardian_associations.1.relationship_type', 'mother');

        $this->assertDatabaseHas('guardians', ['full_name' => 'Carlos Costa', 'school_id' => $school->id]);
        $this->assertDatabaseHas('guardians', ['full_name' => 'Maria Costa', 'school_id' => $school->id]);
        $this->assertCount(2, $response->json('data.guardian_associations'));
    }

    public function test_school_admin_can_link_existing_and_create_new_guardian_with_association_labels(): void
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
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-G-003',
                'first_name' => 'Clara',
                'last_name' => 'Moura',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'emergency contact'],
                    ['relationship_type' => 'emergency contact', 'full_name' => 'New Guardian'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.guardian_associations.0.relationship_type', 'emergency contact')
            ->assertJsonPath('data.guardian_associations.1.relationship_type', 'emergency contact');

        $this->assertDatabaseHas('guardian_student_profile', [
            'guardian_id' => $guardian->id,
            'relationship_type' => 'emergency contact',
            'status' => 'active',
        ]);
    }
}
