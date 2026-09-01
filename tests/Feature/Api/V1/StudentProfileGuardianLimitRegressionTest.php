<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileGuardianLimitRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_relationship_labels_are_allowed_but_duplicate_existing_guardians_are_rejected(): void
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
                'registration_number' => 'STU-GL-001',
                'first_name' => 'Fabio',
                'last_name' => 'Rocha',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['relationship_type' => 'parent', 'full_name' => 'Guardian One'],
                    ['relationship_type' => 'parent', 'full_name' => 'Guardian Two'],
                ],
            ])
            ->assertCreated();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-GL-002',
                'first_name' => 'Gabi',
                'last_name' => 'Rocha',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'father'],
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'mother'],
                ],
            ])
            ->assertUnprocessable();
    }
}
