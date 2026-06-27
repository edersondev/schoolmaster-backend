<?php

declare(strict_types=1);

namespace Tests\Feature\Schools;

use App\Models\Address;
use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAddressManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_address_lifecycle_is_exposed_through_school_operations(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', [
            'name' => 'Address Lifecycle School',
            'code' => 'ADDR-LIFE',
            'address' => $this->validAddressPayload(['number' => '101']),
        ])
            ->assertCreated()
            ->assertJsonPath('data.address.number', '101')
            ->assertJsonMissingPath('data.address_summary')
            ->json('data');

        $this->withToken($token)->getJson('/api/v1/schools/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.address.street', 'Main Street')
            ->assertJsonMissingPath('data.address_summary');

        $this->withToken($token)->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonPath('data.0.address.number', '101')
            ->assertJsonMissingPath('data.0.address_summary');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], [
            'name' => 'Renamed Address Lifecycle School',
        ])
            ->assertOk()
            ->assertJsonPath('data.address.number', '101');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], [
            'address' => $this->validAddressPayload(['number' => '202']),
        ])
            ->assertOk()
            ->assertJsonPath('data.address.number', '202');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], [
            'address' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.address', null);
    }

    public function test_database_rejects_duplicate_active_address_for_same_owner(): void
    {
        $school = School::factory()->create();
        Address::factory()->forSchool($school)->create();

        $this->expectException(QueryException::class);

        Address::factory()->forSchool($school)->create();
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
            'neighborhood' => 'Central',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '12345678',
        ], $overrides);
    }
}
