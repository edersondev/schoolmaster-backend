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
            ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);

        $created = $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'North School',
            'cnpj' => '56.563.930/0001-08',
            'contact_email' => 'north@example.com',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'North School')
            ->assertJsonPath('data.cnpj', '56563930000108')
            ->assertJsonPath('data.address', null)
            ->assertJsonMissingPath('data.code')
            ->assertJsonMissingPath('data.address_summary')
            ->json('data');

        $this->assertDatabaseHas('schools', [
            'uuid' => $created['id'],
            'cnpj' => '56563930000108',
        ]);

        $this->withToken($token)->getJson('/api/v1/schools/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.cnpj', '56563930000108')
            ->assertJsonMissingPath('data.code')
            ->assertJsonPath('data.address', null)
            ->assertJsonMissingPath('data.address_summary');

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

        $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Forbidden School',
            'cnpj' => '11.222.333/0001-81',
        ])
            ->assertForbidden();

        $authorizedToken = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($authorizedToken)->postJson('/api/v1/schools', ['name' => 'Missing CNPJ'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJson(fn ($json) => $json->has('error.details.fields.cnpj')->etc());

        $this->withToken($authorizedToken)->getJson('/api/v1/schools/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_school_cnpj_must_be_valid_and_unique(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        School::factory()->create(['cnpj' => '56563930000108']);

        $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Duplicate School',
            'cnpj' => '56.563.930/0001-08',
        ])
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.cnpj')->etc());

        $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Invalid School',
            'cnpj' => '11.111.111/1111-11',
        ])
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.cnpj')->etc());
    }
}
