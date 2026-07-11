<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolListCombinedFiltersTest extends TestCase
{
    use CreatesSchoolListFilterFixtures;
    use RefreshDatabase;

    public function test_identity_contact_and_location_filters_combine_with_and_semantics(): void
    {
        $target = $this->createFilteredSchool([
            'status' => School::STATUS_ACTIVE,
            'inep_code' => '87654321',
            'document' => '99887766000155',
            'name' => 'Escola Integrada Alfa',
            'email' => 'alfa@example.com',
        ], [
            'city' => 'Recife',
            'state' => 'PE',
        ]);
        $this->createFilteredSchool([
            'status' => School::STATUS_ACTIVE,
            'inep_code' => '87654322',
            'document' => '99887766000156',
            'name' => 'Escola Integrada Alfa',
            'email' => 'alfa-olinda@example.com',
        ], [
            'city' => 'Olinda',
            'state' => 'PE',
        ]);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?status=1&inep_code=87654321&document=99.887.766/0001-55&name=alfa&email=ALFA&city=recife&state=pe')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->uuid);
    }

    public function test_filters_do_not_bypass_platform_school_list_permission(): void
    {
        $this->createFilteredSchool(['name' => 'Hidden Match School']);

        $this->withToken($this->listSchoolsToken([]))
            ->getJson('/api/v1/schools?name=Hidden')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
