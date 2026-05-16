<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_list_create_get_update_activate_and_deactivate_schools(): void
    {
        $user = $this->createPlatformUser();
        $token = $this->bearerTokenFor($user);
        School::factory()->create(['name' => 'Existing School']);

        $this->withToken($token)->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['pagination']]);

        $created = $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'North School',
            'code' => 'NORTH',
            'contact_email' => 'north@example.com',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'North School')
            ->json('data');

        $this->withToken($token)->getJson('/api/v1/schools/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.code', 'NORTH');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], [
            'status' => 'inactive',
        ])->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], [
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_validation_forbidden_and_not_found_cases(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser([]));

        $this->withToken($token)->postJson('/api/v1/schools', [])
            ->assertForbidden();

        $authorizedToken = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($authorizedToken)->postJson('/api/v1/schools', ['name' => 'Missing Code'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->withToken($authorizedToken)->getJson('/api/v1/schools/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }
}
