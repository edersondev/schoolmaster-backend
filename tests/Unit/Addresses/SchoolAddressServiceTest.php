<?php

declare(strict_types=1);

namespace Tests\Unit\Addresses;

use App\Models\School;
use App\Services\Addresses\SchoolAddressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAddressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitted_address_replaces_existing_school_address(): void
    {
        $school = School::factory()->create();
        $service = app(SchoolAddressService::class);

        $service->applySubmittedAddress($school, [
            'address' => [
                'street' => 'First Street',
                'number' => '100',
                'neighborhood' => 'Central',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '10000000',
            ],
        ]);

        $service->applySubmittedAddress($school, [
            'address' => [
                'street' => 'Second Street',
                'number' => '200',
                'complement' => 'Suite 1',
                'neighborhood' => 'Downtown',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'zip_code' => '20000000',
                'country' => 'Brazil',
            ],
        ]);

        $this->assertSame(1, $school->address()->count());
        $this->assertDatabaseHas('addresses', [
            'school_id' => $school->id,
            'addressable_type' => School::class,
            'addressable_id' => $school->id,
            'street' => 'Second Street',
            'number' => '200',
            'zip_code' => '20000000',
        ]);
    }

    public function test_omitted_address_payload_leaves_existing_address_unchanged(): void
    {
        $school = School::factory()->create();
        $service = app(SchoolAddressService::class);

        $service->applySubmittedAddress($school, [
            'address' => [
                'street' => 'Stable Street',
                'number' => '300',
                'neighborhood' => 'Central',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '30000000',
            ],
        ]);
        $service->applySubmittedAddress($school, []);

        $this->assertSame('Stable Street', $school->refresh()->address->street);
    }
}
