<?php

declare(strict_types=1);

namespace Tests\Feature\AddressLookup;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AddressLookupApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_authenticated_user_can_lookup_address_by_masked_zip_code(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response($this->viaCepPayload()),
        ]);

        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/01001-000')
            ->assertOk()
            ->assertJsonPath('data.zip_code', '01001000')
            ->assertJsonPath('data.formatted_zip_code', '01001-000')
            ->assertJsonPath('data.street', 'Praça da Sé')
            ->assertJsonPath('data.city', 'São Paulo')
            ->assertJsonPath('data.state', 'SP')
            ->assertJsonPath('data.country', 'BR')
            ->assertJsonPath('data.source', 'viacep');
    }

    public function test_unauthenticated_user_cannot_lookup_address(): void
    {
        $this->getJson('/api/v1/address-lookups/01001000')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_invalid_zip_code_returns_validation_error(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/0100A000')
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.zip_code')->etc());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/010010000')
            ->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.details.fields.zip_code')->etc());
    }

    public function test_valid_but_unknown_zip_code_returns_not_found(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/99999999/json/' => Http::response(['erro' => true]),
        ]);

        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/99999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_upstream_failure_returns_temporary_unavailable(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([], 500),
        ]);

        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/01001000')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'temporary_unavailable');
    }

    public function test_successful_lookup_is_cached(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::sequence()
                ->push($this->viaCepPayload())
                ->push([], 500),
        ]);

        $token = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/01001000')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/address-lookups/01001000')
            ->assertOk();

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, string>
     */
    private function viaCepPayload(): array
    {
        return [
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'complemento' => 'lado ímpar',
            'unidade' => '',
            'bairro' => 'Sé',
            'localidade' => 'São Paulo',
            'uf' => 'SP',
            'estado' => 'São Paulo',
            'regiao' => 'Sudeste',
            'ibge' => '3550308',
            'gia' => '1004',
            'ddd' => '11',
            'siafi' => '7107',
        ];
    }
}
