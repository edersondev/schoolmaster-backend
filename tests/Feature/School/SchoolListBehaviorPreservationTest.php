<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolListBehaviorPreservationTest extends TestCase
{
    use CreatesSchoolListFilterFixtures;
    use RefreshDatabase;

    public function test_filtered_responses_preserve_paginated_envelope_and_page_metadata(): void
    {
        $this->createFilteredSchool(['name' => 'North Filter A']);
        $this->createFilteredSchool(['name' => 'North Filter B']);
        $this->createFilteredSchool(['name' => 'South Filter']);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?name=North&page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'document', 'email', 'address'],
                ],
                'meta' => ['page', 'per_page', 'total'],
            ]);
    }

    public function test_no_result_filters_return_successful_empty_paginated_response(): void
    {
        $this->createFilteredSchool(['name' => 'Visible School']);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?name=NoSuchSchool')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_filtered_list_preserves_created_at_desc_sorting(): void
    {
        $first = $this->createFilteredSchool(['name' => 'Sorted Filter A']);
        $second = $this->createFilteredSchool(['name' => 'Sorted Filter B']);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?name=Sorted')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->uuid)
            ->assertJsonPath('data.1.id', $first->uuid);
    }
}
