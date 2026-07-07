<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SchoolCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_create_school_profile_with_all_tab_fields_and_logo(): void
    {
        Storage::fake('public');

        $token = $this->bearerTokenFor($this->createPlatformUser());
        $payload = $this->validSchoolProfilePayload([
            'name' => 'Full Profile School',
            'document' => '56.563.930/0001-08',
            'email' => 'PROFILE@EXAMPLE.COM',
            'logo_file' => UploadedFile::fake()->create('logo.png', 16, 'image/png'),
        ]);

        $school = $this->withToken($token)->post('/api/v1/schools', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Full Profile School')
            ->assertJsonPath('data.document', '56563930000108')
            ->assertJsonPath('data.email', 'profile@example.com')
            ->assertJsonPath('data.status', 1)
            ->assertJsonPath('data.address.city', 'Sao Paulo')
            ->assertJsonPath('data.administrative_type_id', 1)
            ->assertJsonPath('data.education_level_ids.0', 1)
            ->assertJsonPath('data.modality_ids.0', 1)
            ->assertJsonMissingPath('data.cnpj')
            ->json('data');

        $this->assertIsString($school['logo_path']);
        Storage::disk('public')->assertExists($school['logo_path']);

        $this->assertDatabaseHas('schools', [
            'uuid' => $school['id'],
            'document' => '56563930000108',
            'cnpj' => '56563930000108',
            'normalized_email' => 'profile@example.com',
            'status' => 1,
        ]);
    }

    public function test_create_without_logo_persists_profile_with_null_logo_path(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'No Logo School',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'No Logo School')
            ->assertJsonPath('data.logo_path', null);
    }

    public function test_create_requires_address_institutional_fields_and_numeric_status(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());
        $payload = $this->validSchoolProfilePayload([
            'status' => 'active',
            'address' => null,
            'administrative_type_id' => null,
            'education_level_ids' => [],
        ]);

        $this->withToken($token)->postJson('/api/v1/schools', $payload)
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json
                ->has('error.details.fields.status')
                ->has('error.details.fields.address')
                ->has('error.details.fields.administrative_type_id')
                ->etc());
    }

    public function test_create_rejects_invalid_check_digits_and_duplicate_soft_deleted_identity_fields(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '11.111.111/1111-11',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.document')->etc());

        School::factory()->create([
            'inep_code' => '87654321',
            'document' => '56563930000108',
            'email' => 'duplicate@example.com',
            'normalized_email' => 'duplicate@example.com',
            'deleted_at' => now(),
        ]);

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'inep_code' => '87654321',
            'document' => '56.563.930/0001-08',
            'email' => 'DUPLICATE@example.com',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json
                ->has('error.details.fields.inep_code')
                ->has('error.details.fields.document')
                ->has('error.details.fields.email')
                ->etc());
    }

    public function test_create_rejects_legacy_cnpj_field(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'cnpj' => '56563930000108',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.cnpj')->etc());
    }

    public function test_create_rejects_case_insensitive_duplicate_email(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        School::factory()->create([
            'email' => 'duplicate@example.com',
            'normalized_email' => 'duplicate@example.com',
        ]);

        $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'email' => 'DUPLICATE@EXAMPLE.COM',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.email')->etc());
    }
}
