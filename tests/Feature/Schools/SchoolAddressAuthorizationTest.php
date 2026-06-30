<?php

declare(strict_types=1);

namespace Tests\Feature\Schools;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAddressAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_mutation_uses_school_management_authorization_boundary(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createPlatformUser(['schools.view']));

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address' => [
                'street' => 'Forbidden Street',
                'number' => '123',
                'neighborhood' => 'Central',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '12345678',
            ],
        ])
            ->assertForbidden();

        $this->assertDatabaseMissing('addresses', [
            'school_id' => $school->id,
            'street' => 'Forbidden Street',
        ]);
    }

    public function test_direct_school_id_in_address_payload_is_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)->patchJson('/api/v1/schools/'.$school->uuid, [
            'address' => [
                'school_id' => $otherSchool->id,
                'street' => 'Spoofed Street',
                'number' => '123',
                'neighborhood' => 'Central',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '12345678',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseMissing('addresses', [
            'school_id' => $otherSchool->id,
            'street' => 'Spoofed Street',
        ]);
    }
}
