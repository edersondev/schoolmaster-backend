<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\School;
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
