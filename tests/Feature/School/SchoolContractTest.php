<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\SchoolInstitutionalLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_response_matches_profile_contract_and_omits_legacy_fields(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $created = $this->withToken($token)->postJson('/api/v1/schools', $this->validSchoolProfilePayload([
            'document' => '56.563.930/0001-08',
        ]))->assertCreated()->json('data');

        $this->withToken($token)->patchJson('/api/v1/schools/'.$created['id'], $this->validSchoolProfilePayload([
            'document' => $created['document'],
            'name' => 'Contract Updated School',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Contract Updated School')
            ->assertJsonMissingPath('data.cnpj');

        $this->withToken($token)->getJson('/api/v1/schools/'.$created['id'])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'inep_code',
                    'status',
                    'name',
                    'trade_name',
                    'legal_name',
                    'document',
                    'email',
                    'phone',
                    'website',
                    'description',
                    'address' => [
                        'street',
                        'number',
                        'complement',
                        'neighborhood',
                        'city',
                        'state',
                        'zip_code',
                        'country',
                    ],
                    'administrative_type_id',
                    'legal_nature_id',
                    'management_type_id',
                    'pedagogical_approach_id',
                    'education_level_ids',
                    'modality_ids',
                    'timezone',
                    'language',
                    'logo_path',
                    'primary_color',
                    'secondary_color',
                ],
            ])
            ->assertJsonMissingPath('data.cnpj')
            ->assertJsonMissingPath('data.code')
            ->assertJsonMissingPath('data.address_summary');

        $this->withToken($token)->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonMissingPath('data.0.cnpj')
            ->assertJsonPath('data.0.document', '56563930000108');
    }

    public function test_school_lookup_endpoints_return_active_sorted_contract_options(): void
    {
        $this->seedSchoolInstitutionalLookups();
        $token = $this->bearerTokenFor($this->createPlatformUser(['schools.view']));

        SchoolInstitutionalLookup::query()->create([
            'group' => SchoolInstitutionalLookup::ADMINISTRATIVE_TYPE,
            'option_id' => 99,
            'label' => 'Inactive',
            'status' => 0,
            'sort_order' => 0,
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/school-lookups/administrative-types')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'label', 'status', 'sort_order'],
                ],
            ])
            ->json('data');

        $this->assertNotEmpty($response);
        $this->assertSame(1, $response[0]['status']);
        $this->assertNotContains(99, array_column($response, 'id'));
    }
}
