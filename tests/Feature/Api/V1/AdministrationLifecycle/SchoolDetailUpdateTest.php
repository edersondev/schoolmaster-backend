<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_and_update_school_lifecycle_fields(): void
    {
        $school = School::factory()->create(['name' => 'Old Name']);
        $token = $this->bearerTokenFor($this->createPlatformUser(['schools.view', 'schools.manage', 'schools.lifecycle']));

        $this->withToken($token)
            ->getJson("/api/v1/schools/{$school->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $school->uuid);

        $this->withToken($token)
            ->patchJson("/api/v1/schools/{$school->uuid}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('lifecycle_histories', [
            'resource_uuid' => $school->uuid,
            'operation' => 'updated',
        ]);
    }

    public function test_school_user_cannot_manage_school_lifecycle_record(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->patchJson("/api/v1/schools/{$school->uuid}", ['name' => 'Denied'])
            ->assertForbidden();
    }
}
