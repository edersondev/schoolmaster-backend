<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolListInstitutionalFiltersTest extends TestCase
{
    use CreatesSchoolListFilterFixtures;
    use RefreshDatabase;

    public function test_institutional_filters_match_single_approved_lookup_values_exactly(): void
    {
        $this->seedSchoolInstitutionalLookups();

        $target = $this->createFilteredSchool([
            'administrative_type_id' => 2,
            'legal_nature_id' => 2,
            'management_type_id' => 3,
            'pedagogical_approach_id' => 4,
        ]);
        $this->createFilteredSchool([
            'administrative_type_id' => 1,
            'legal_nature_id' => 2,
            'management_type_id' => 3,
            'pedagogical_approach_id' => 4,
        ]);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?administrative_type_id=2&legal_nature_id=2&management_type_id=3&pedagogical_approach_id=4')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->uuid);
    }

    public function test_institutional_filters_reject_unknown_and_multi_value_inputs(): void
    {
        $this->seedSchoolInstitutionalLookups();

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?administrative_type_id=999')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?status[]=1&status[]=0')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }
}
