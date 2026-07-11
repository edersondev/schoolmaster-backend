<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolListIdentityFiltersTest extends TestCase
{
    use CreatesSchoolListFilterFixtures;
    use RefreshDatabase;

    public function test_status_filter_matches_numeric_status_exactly(): void
    {
        $active = $this->createFilteredSchool(['status' => School::STATUS_ACTIVE]);
        $inactive = $this->createFilteredSchool(['status' => School::STATUS_INACTIVE]);

        $response = $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?status=0')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->json('data');

        $this->assertSame([$inactive->uuid], $this->responseSchoolIds($response));
        $this->assertNotContains($active->uuid, $this->responseSchoolIds($response));
    }

    public function test_inep_and_document_filters_match_exact_normalized_digits(): void
    {
        $target = $this->createFilteredSchool([
            'inep_code' => '12345678',
            'document' => '12345678000195',
        ]);
        $this->createFilteredSchool([
            'inep_code' => '12345679',
            'document' => '12345678000196',
        ]);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?inep_code=12345678&document=12.345.678/0001-95')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->uuid);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?inep_code=1234&document=12.345.678/0001')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_cnpj_query_alias_is_rejected(): void
    {
        $this->createFilteredSchool(['document' => '12345678000195']);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?cnpj=12.345.678/0001-95')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.cnpj.0', 'This field is not documented for this request.');
    }
}
