<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SchoolUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_update_school_profile_tabs_without_changing_document(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'name' => 'Update Source School',
            'document' => '56.563.930/0001-08',
            'email' => 'source@example.com',
        ]))->assertCreated()->json('data');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'name' => 'Updated Tab School',
            'trade_name' => 'Updated Trade',
            'document' => $created['document'],
            'email' => 'UPDATED@example.com',
            'status' => 1,
            'address' => [
                'street' => 'Second Street',
                'number' => '456',
                'complement' => 'Suite 7',
                'neighborhood' => 'West',
                'city' => 'Campinas',
                'state' => 'SP',
                'zip_code' => '13000000',
                'country' => 'Brazil',
            ],
            'primary_color' => '#047857',
            'secondary_color' => '#DC2626',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Tab School')
            ->assertJsonPath('data.trade_name', 'Updated Trade')
            ->assertJsonPath('data.document', $created['document'])
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.address.city', 'Campinas')
            ->assertJsonPath('data.primary_color', '#047857')
            ->assertJsonMissingPath('data.cnpj');

        $this->assertDatabaseHas('schools', [
            'uuid' => $created['id'],
            'name' => 'Updated Tab School',
            'document' => $created['document'],
            'normalized_email' => 'updated@example.com',
        ]);
    }

    public function test_update_rejects_document_changes(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '56.563.930/0001-08',
        ]))->assertCreated()->json('data');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => '11.444.777/0001-61',
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.document')->etc());
    }

    public function test_update_rejects_omitted_or_incomplete_address_and_duplicate_identity_fields(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $first = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'inep_code' => '12345678',
            'document' => '56.563.930/0001-08',
            'email' => 'first@example.com',
        ]))->assertCreated()->json('data');

        $second = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'inep_code' => '87654321',
            'document' => '11.444.777/0001-61',
            'email' => 'second@example.com',
        ]))->assertCreated()->json('data');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$second['id'], $this->validSchoolProfilePayload([
            'document' => $second['document'],
            'address' => null,
        ]))
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.address')->etc());

        $payload = $this->validSchoolProfilePayload([
            'document' => $second['document'],
            'inep_code' => $first['inep_code'],
            'email' => 'FIRST@example.com',
        ]);
        unset($payload['address']['number']);

        $this->withToken($token)->patchJson('/api/v1/schools/'.$second['id'], $payload)
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json
                ->has('error.details.fields.inep_code')
                ->has('error.details.fields.email')
                ->has('error.details.fields.address')
                ->etc());
    }

    public function test_update_preserves_logo_without_new_file_and_replaces_old_logo_after_success(): void
    {
        Storage::fake('public');

        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->post('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '56.563.930/0001-08',
            'logo_file' => UploadedFile::fake()->create('first.png', 8, 'image/png'),
        ]))->assertCreated()->json('data');

        $originalLogo = $created['logo_path'];

        $withoutLogo = $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'name' => 'Logo Preserved School',
        ]))->assertOk()->json('data');

        $this->assertSame($originalLogo, $withoutLogo['logo_path']);
        Storage::disk('public')->assertExists($originalLogo);

        $withReplacement = $this->withToken($token)->patch('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'name' => 'Logo Replaced School',
            'logo_file' => UploadedFile::fake()->create('second.png', 8, 'image/png'),
        ]))->assertOk()->json('data');

        $this->assertNotSame($originalLogo, $withReplacement['logo_path']);
        Storage::disk('public')->assertMissing($originalLogo);
        Storage::disk('public')->assertExists($withReplacement['logo_path']);
    }

    public function test_existing_simultaneous_edit_behavior_allows_last_valid_update_to_win(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '56.563.930/0001-08',
        ]))->assertCreated()->json('data');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'name' => 'First Save',
        ]))->assertOk();

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'name' => 'Second Save',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Second Save');
    }

    public function test_status_deactivation_uses_lifecycle_dependency_checks(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '56.563.930/0001-08',
        ]))->assertCreated()->json('data');

        $school = School::query()->where('uuid', $created['id'])->firstOrFail();
        User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'status' => 0,
        ]))->assertConflict();
    }
}
