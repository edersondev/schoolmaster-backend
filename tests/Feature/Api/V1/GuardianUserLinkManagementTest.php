<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianUserLinkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_deactivate_guardian_user_link(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $guardian = $this->guardian($school);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $created = $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $user->uuid])
            ->assertCreated()
            ->assertJsonPath('data.guardian_id', $guardian->uuid)
            ->assertJsonPath('data.user_id', $user->uuid);

        $duplicateRejectedByDatabase = false;
        try {
            GuardianUserLink::query()->create([
                'school_id' => $school->id,
                'guardian_id' => $guardian->id,
                'user_id' => $user->id,
                'created_by_user_id' => $admin->id,
                'status' => 'active',
            ]);
        } catch (QueryException) {
            $duplicateRejectedByDatabase = true;
        }

        $this->assertTrue($duplicateRejectedByDatabase);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links/{$created->json('data.id')}/deactivate", ['reason' => 'School review.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $user->uuid])
            ->assertCreated();
    }

    public function test_duplicate_cross_tenant_and_unauthorized_link_attempts_are_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $unauthorized = User::factory()->create(['school_id' => $school->id]);
        $guardian = $this->guardian($school);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $otherUser = User::factory()->create(['school_id' => $otherSchool->id, 'status' => 'active']);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $user->uuid])
            ->assertCreated();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $user->uuid])
            ->assertConflict();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $otherUser->uuid])
            ->assertUnprocessable();

        $this->withHeaders($this->headers($unauthorized, $school))
            ->postJson("/api/v1/guardians/{$guardian->uuid}/user-links", ['user_id' => $user->uuid])
            ->assertForbidden();
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }

    private function guardian(School $school): Guardian
    {
        return Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => fake()->name(),
            'relationship_type' => 'guardian',
            'status' => 'active',
        ]);
    }
}
