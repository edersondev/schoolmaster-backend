<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'full_name' => 'Old User']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->uuid);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$user->uuid}", ['full_name' => 'New User'])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'New User');
    }

    public function test_user_detail_rejects_cross_tenant_access(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view']);
        $user = User::factory()->create(['school_id' => $otherSchool->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertNotFound();
    }
}
