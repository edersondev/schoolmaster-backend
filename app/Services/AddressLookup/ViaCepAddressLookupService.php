<?php

declare(strict_types=1);

namespace App\Services\AddressLookup;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class ViaCepAddressLookupService
{
    /**
     * @return array<string, string|null>
     */
    public function lookup(string $zipCode): array
    {
        $normalizedZipCode = preg_replace('/\D+/', '', $zipCode) ?? '';
        $ttlSeconds = (int) config('services.viacep.cache_ttl_seconds', 86400);

        return Cache::remember(
            "address_lookup:viacep:{$normalizedZipCode}",
            $ttlSeconds,
            fn (): array => $this->fetch($normalizedZipCode),
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function fetch(string $zipCode): array
    {
        $baseUrl = rtrim((string) config('services.viacep.base_url', 'https://viacep.com.br'), '/');
        $timeoutSeconds = (int) config('services.viacep.timeout_seconds', 3);

        try {
            $response = Http::acceptJson()
                ->timeout($timeoutSeconds)
                ->get("{$baseUrl}/ws/{$zipCode}/json/");
        } catch (ConnectionException $exception) {
            throw new AddressLookupUnavailableException('ViaCEP lookup is temporarily unavailable.', previous: $exception);
        }

        if ($response->serverError()) {
            throw new AddressLookupUnavailableException('ViaCEP lookup is temporarily unavailable.');
        }

        if ($response->failed()) {
            throw new AddressLookupUnavailableException('ViaCEP lookup failed.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new AddressLookupUnavailableException('ViaCEP returned an invalid response.');
        }

        if (($payload['erro'] ?? false) === true) {
            throw new AddressLookupNotFoundException('Address was not found for the provided zip code.');
        }

        return $this->normalize($zipCode, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string|null>
     */
    private function normalize(string $zipCode, array $payload): array
    {
        $formattedZipCode = $this->nullableString($payload['cep'] ?? null)
            ?? substr($zipCode, 0, 5).'-'.substr($zipCode, 5);

        return [
            'zip_code' => preg_replace('/\D+/', '', $formattedZipCode) ?: $zipCode,
            'formatted_zip_code' => $formattedZipCode,
            'street' => $this->nullableString($payload['logradouro'] ?? null),
            'complement' => $this->nullableString($payload['complemento'] ?? null),
            'neighborhood' => $this->nullableString($payload['bairro'] ?? null),
            'city' => $this->nullableString($payload['localidade'] ?? null),
            'state' => $this->nullableString($payload['uf'] ?? null),
            'state_name' => $this->nullableString($payload['estado'] ?? null),
            'region' => $this->nullableString($payload['regiao'] ?? null),
            'ibge_code' => $this->nullableString($payload['ibge'] ?? null),
            'gia_code' => $this->nullableString($payload['gia'] ?? null),
            'area_code' => $this->nullableString($payload['ddd'] ?? null),
            'siafi_code' => $this->nullableString($payload['siafi'] ?? null),
            'country' => 'BR',
            'source' => 'viacep',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
