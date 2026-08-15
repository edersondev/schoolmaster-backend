<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_list_user_with_same_school_role(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $token = $this->bearerTokenFor($admin);
        $role = Role::query()->where('school_id', $school->id)->where('scope', 'school')->firstOrFail();

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'Student User',
                'email' => 'student@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.school_id', $school->uuid)
            ->json('data');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);

        $this->assertSame('active', $created['status']);
    }

    public function test_school_admin_can_create_one_invitation_ready_user_without_sending_invitation(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'Invited User',
                'email' => 'invited-user@example.test',
                'role_ids' => [$role->uuid],
                'account_setup_mode' => 'invitation',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'invited');

        $this->assertDatabaseCount('account_invitations', 0);
        $this->assertSame(1, User::query()->where('email', 'invited-user@example.test')->count());
        $this->assertDatabaseHas('users', [
            'uuid' => $response->json('data.id'),
            'status' => 'invited',
        ]);
    }

    public function test_user_creation_rejects_unknown_account_setup_mode(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'Invalid Setup',
                'email' => 'invalid-setup@example.test',
                'role_ids' => [$role->uuid],
                'account_setup_mode' => 'automatic',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_user_creation_rejects_cross_tenant_role(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $otherRole = Role::query()->create([
            'school_id' => $otherSchool->id,
            'scope' => 'school',
            'name' => 'Other Role',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'Invalid User',
                'email' => 'invalid@example.test',
                'role_ids' => [$otherRole->uuid],
            ])
            ->assertUnprocessable();
    }

    public function test_platform_user_can_list_platform_users_without_school_context(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);
    }

    public function test_platform_list_excludes_school_users_and_school_list_excludes_platform_users(): void
    {
        $school = School::factory()->create();
        $platformActor = $this->createPlatformUser(['schools.view']);
        $schoolActor = $this->createSchoolAdmin($school, ['users.view']);
        $platformTarget = User::factory()->create(['school_id' => null]);
        $schoolTarget = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($platformActor))
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $platformTarget->uuid])
            ->assertJsonMissing(['id' => $schoolTarget->uuid]);

        $this->withToken($this->bearerTokenFor($schoolActor))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $schoolTarget->uuid])
            ->assertJsonMissing(['id' => $platformTarget->uuid]);
    }

    public function test_list_requires_authority_for_the_preselected_mode(): void
    {
        $school = School::factory()->create();

        $this->withToken($this->bearerTokenFor($this->createLimitedPlatformUser()))
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($this->createSchoolAdmin($school, ['account_lifecycle.manage'])))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_system_administrator_can_list_in_platform_and_school_modes(): void
    {
        $school = School::factory()->create();
        $master = $this->createSystemAdministrator();
        $token = $this->bearerTokenFor($master);

        $this->withToken($token)->getJson('/api/v1/users')->assertOk();
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/users')->assertOk();
    }

    public function test_user_listing_accepts_documented_comma_separated_sort(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users?sort=full_name,-email')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);
    }
}
