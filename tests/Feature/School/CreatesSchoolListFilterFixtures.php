<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\School;

trait CreatesSchoolListFilterFixtures
{
    private static int $schoolListFilterSequence = 7000;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $address
     */
    protected function createFilteredSchool(array $attributes = [], array $address = []): School
    {
        $sequence = self::$schoolListFilterSequence++;

        $school = School::factory()->create(array_replace([
            'inep_code' => str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'name' => 'Filter School '.$sequence,
            'trade_name' => 'Filter School',
            'legal_name' => 'Filter School Legal '.$sequence,
            'document' => str_pad((string) $sequence, 14, '0', STR_PAD_LEFT),
            'email' => 'filter'.$sequence.'@example.com',
            'administrative_type_id' => 1,
            'legal_nature_id' => 1,
            'management_type_id' => 1,
            'pedagogical_approach_id' => 1,
        ], $attributes));

        $school->address()->create(array_replace([
            'school_id' => $school->id,
            'street' => 'Main Street',
            'number' => '123',
            'neighborhood' => 'Central',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '12345678',
            'country' => 'Brazil',
        ], $address));

        return $school->refresh()->load('address');
    }

    protected function listSchoolsToken(array $permissions = ['schools.view']): string
    {
        return $this->bearerTokenFor($this->createPlatformUser($permissions));
    }

    /**
     * @return array<int, string>
     */
    protected function responseSchoolIds(array $responseData): array
    {
        return array_column($responseData, 'id');
    }
}
