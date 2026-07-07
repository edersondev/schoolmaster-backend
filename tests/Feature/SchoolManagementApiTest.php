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

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'North School',
            'document' => '56.563.930/0001-08',
            'email' => 'north@example.com',
        ]))->assertCreated()
            ->assertJsonPath('data.name', 'North School')
            ->assertJsonPath('data.document', '56563930000108')
            ->assertJsonPath('data.status', 1)
            ->assertJsonPath('data.address.street', 'Main Street')
            ->assertJsonMissingPath('data.code')
            ->assertJsonMissingPath('data.cnpj')
            ->assertJsonMissingPath('data.address_summary')
            ->json('data');

        $this->assertDatabaseHas('schools', [
            'uuid' => $created['id'],
            'document' => '56563930000108',
            'normalized_email' => 'north@example.com',
        ]);

        $this->withToken($token)->getJson('/api/v1/schools/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.document', '56563930000108')
            ->assertJsonMissingPath('data.code')
            ->assertJsonMissingPath('data.cnpj')
            ->assertJsonPath('data.address.street', 'Main Street')
            ->assertJsonMissingPath('data.address_summary');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'status' => 0,
            'document' => $created['document'],
            'email' => 'north-updated@example.com',
        ]))->assertOk()
            ->assertJsonPath('data.status', 0);

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'status' => 1,
            'document' => $created['document'],
            'email' => 'north-reactivated@example.com',
        ]))->assertOk()
            ->assertJsonPath('data.status', 1);
    }

    public function test_validation_forbidden_and_not_found_cases(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser([]));

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'Forbidden School',
        ]))
            ->assertForbidden();

        $authorizedToken = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($authorizedToken)->postJson('/api/v1/schools', ['name' => 'Missing Document'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJson(fn ($json) => $json->has('error.details.fields.document')->etc());

        $this->withToken($authorizedToken)->getJson('/api/v1/schools/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_school_document_must_be_valid_and_unique(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        School::factory()->create(['document' => '56563930000108']);

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'Duplicate School',
            'document' => '56.563.930/0001-08',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.document')->etc());

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'Invalid School',
            'document' => '11.111.111/1111-11',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.document')->etc());
    }
}
