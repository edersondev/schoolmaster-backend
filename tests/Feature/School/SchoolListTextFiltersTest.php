<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolListTextFiltersTest extends TestCase
{
    use CreatesSchoolListFilterFixtures;
    use RefreshDatabase;

    public function test_name_email_city_and_state_filters_use_contains_matching_ignoring_case_and_accents(): void
    {
        $target = $this->createFilteredSchool([
            'name' => 'Escola São Rafael',
            'email' => 'Contato.Rafael@example.com',
        ], [
            'city' => 'São Luís',
            'state' => 'MA',
        ]);
        $this->createFilteredSchool([
            'name' => 'Colegio Santa Clara',
            'email' => 'clara@example.com',
        ], [
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?name=sao%20rafa&email=RAFAEL&city=sao%20lui&state=ma')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->uuid);
    }

    public function test_text_filters_combine_with_and_semantics(): void
    {
        $target = $this->createFilteredSchool([
            'name' => 'Centro Educacional Norte',
            'email' => 'norte@example.com',
        ], [
            'city' => 'Manaus',
            'state' => 'AM',
        ]);
        $this->createFilteredSchool([
            'name' => 'Centro Educacional Sul',
            'email' => 'sul@example.com',
        ], [
            'city' => 'Manaus',
            'state' => 'AM',
        ]);

        $this->withToken($this->listSchoolsToken())
            ->getJson('/api/v1/schools?name=centro&email=norte&city=man&state=am')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->uuid);
    }
}
