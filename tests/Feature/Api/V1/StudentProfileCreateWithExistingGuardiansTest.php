<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileCreateWithExistingGuardiansTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_same_school_guardian_can_be_linked_with_association_specific_label(): void
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
                'registration_number' => 'STU-EG-001',
                'first_name' => 'Helena',
                'last_name' => 'Nunes',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'emergency contact'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.guardian_associations.0.id', $guardian->uuid)
            ->assertJsonPath('data.guardian_associations.0.relationship_type', 'emergency contact')
            ->assertJsonPath('data.guardian_associations.0.status', 'active');
    }
}
