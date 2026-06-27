<?php

declare(strict_types=1);

namespace Tests\Feature\Schools;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAddressValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_create_school_with_structured_address(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'North School',
            'code' => 'NORTH-ADDR',
            'contact_email' => 'north-address@example.com',
            'address' => $this->validAddressPayload([
                'country' => null,
                'complement' => 'Block B',
            ]),
        ])
            ->assertCreated()
            ->assertJsonPath('data.address.street', 'Main Street')
            ->assertJsonPath('data.address.number', '123')
            ->assertJsonPath('data.address.complement', 'Block B')
            ->assertJsonPath('data.address.country', null);

        $school = School::query()->where('code', 'NORTH-ADDR')->firstOrFail();

        $this->assertDatabaseHas('addresses', [
            'school_id' => $school->id,
            'addressable_type' => School::class,
            'addressable_id' => $school->id,
            'street' => 'Main Street',
            'number' => '123',
            'zip_code' => '12345678',
        ]);
    }

    public function test_school_address_validation_requires_structured_fields_and_digit_only_values(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $response = $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Invalid Address School',
            'code' => 'INVALID-ADDR',
            'address' => [
                'street' => '',
                'number' => '12A',
                'neighborhood' => '',
                'city' => '',
                'state' => '',
                'zip_code' => '12345-678',
                'unexpected' => 'not allowed',
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $fields = $response->json('error.details.fields');

        $this->assertArrayHasKey('address', $fields);
        $this->assertArrayHasKey('address.street', $fields);
        $this->assertArrayHasKey('address.number', $fields);
        $this->assertArrayHasKey('address.neighborhood', $fields);
        $this->assertArrayHasKey('address.city', $fields);
        $this->assertArrayHasKey('address.state', $fields);
        $this->assertArrayHasKey('address.zip_code', $fields);
    }

    public function test_school_create_and_update_reject_legacy_address_summary(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Legacy Address School',
            'code' => 'LEGACY-ADDR',
            'address_summary' => 'Old free-form address',
        ])
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.address_summary')->etc());

        $school = School::factory()->create();

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address_summary' => 'Old free-form address',
        ])
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.address_summary')->etc());
    }

    public function test_platform_user_can_replace_and_remove_school_address(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());
        $school = School::factory()->create();

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address' => $this->validAddressPayload(['number' => '321']),
        ])
            ->assertOk()
            ->assertJsonPath('data.address.number', '321');

        $this->assertDatabaseHas('addresses', [
            'school_id' => $school->id,
            'addressable_type' => School::class,
            'addressable_id' => $school->id,
            'number' => '321',
            'deleted_at' => null,
        ]);

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address' => $this->validAddressPayload(['number' => '654']),
        ])
            ->assertOk()
            ->assertJsonPath('data.address.number', '654');

        $this->assertSame(1, $school->address()->count());

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.address', null);

        $this->assertSame(0, $school->address()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validAddressPayload(array $overrides = []): array
    {
        return array_merge([
            'street' => 'Main Street',
            'number' => '123',
            'complement' => null,
            'neighborhood' => 'Central',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '12345678',
            'country' => 'Brazil',
        ], $overrides);
    }
}
