<?php

declare(strict_types=1);

namespace Tests\Unit\AddressLookup;

use App\Services\AddressLookup\AddressLookupNotFoundException;
use App\Services\AddressLookup\AddressLookupUnavailableException;
use App\Services\AddressLookup\ViaCepAddressLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ViaCepAddressLookupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_maps_viacep_payload_to_lookup_contract(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => '',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'estado' => 'São Paulo',
                'regiao' => 'Sudeste',
                'ibge' => '3550308',
                'gia' => '1004',
                'ddd' => '11',
                'siafi' => '7107',
            ]),
        ]);

        $lookup = app(ViaCepAddressLookupService::class)->lookup('01001-000');

        $this->assertSame('01001000', $lookup['zip_code']);
        $this->assertSame('01001-000', $lookup['formatted_zip_code']);
        $this->assertSame('Praça da Sé', $lookup['street']);
        $this->assertNull($lookup['complement']);
        $this->assertSame('São Paulo', $lookup['city']);
        $this->assertSame('SP', $lookup['state']);
        $this->assertSame('viacep', $lookup['source']);
    }

    public function test_unknown_zip_code_throws_not_found_exception(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/99999999/json/' => Http::response(['erro' => true]),
        ]);

        $this->expectException(AddressLookupNotFoundException::class);

        app(ViaCepAddressLookupService::class)->lookup('99999999');
    }

    public function test_invalid_upstream_json_throws_unavailable_exception(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response('not-json'),
        ]);

        $this->expectException(AddressLookupUnavailableException::class);

        app(ViaCepAddressLookupService::class)->lookup('01001000');
    }
}
