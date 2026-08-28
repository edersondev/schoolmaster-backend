<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_list_guardian_with_student_association(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $student = User::factory()->create(['school_id' => $school->id]);
        $profile = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $student->id]);

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/guardians', [
                'full_name' => 'Guardian User',
                'relationship_type' => 'parent',
                'contact_email' => 'guardian@example.test',
                'student_profile_ids' => [$profile->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.school_id', $school->uuid)
            ->json('data');

        $this->assertDatabaseHas('guardian_student_profile', ['student_profile_id' => $profile->id]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardians')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_guardian_creation_rejects_cross_tenant_student_profile_without_partial_create(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $student = User::factory()->create(['school_id' => $otherSchool->id]);
        $profile = StudentProfile::query()->create(['school_id' => $otherSchool->id, 'user_id' => $student->id]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/guardians', [
                'full_name' => 'Invalid Guardian',
                'relationship_type' => 'parent',
                'student_profile_ids' => [$profile->uuid],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('guardians', ['full_name' => 'Invalid Guardian']);
    }

    public function test_school_admin_can_filter_guardians_by_full_name_contact_email_and_status(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $matchingGuardian = Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => 'Maria da Silva',
            'relationship_type' => 'parent',
            'contact_email' => 'Maria.Guardian@Example.test',
            'status' => 'active',
        ]);

        Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => 'Maria Souza',
            'relationship_type' => 'parent',
            'contact_email' => 'maria@example.test',
            'status' => 'inactive',
        ]);

        Guardian::query()->create([
            'school_id' => $otherSchool->id,
            'full_name' => 'Maria da Silva',
            'relationship_type' => 'parent',
            'contact_email' => 'Maria.Guardian@Example.test',
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardians?full_name=MARIA%20DA&contact_email=guardian%40example&status=active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingGuardian->uuid)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_guardian_list_rejects_invalid_or_undocumented_filters(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardians?full_name='.str_repeat('a', 256).'&status=pending')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['fields' => ['full_name', 'status']]]]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardians?relationship_type=parent')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['fields' => ['relationship_type']]]]);
    }
}
