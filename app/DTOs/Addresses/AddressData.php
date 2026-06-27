<?php

declare(strict_types=1);

namespace App\DTOs\Addresses;

final readonly class AddressData
{
    public function __construct(
        public string $street,
        public string $number,
        public ?string $complement,
        public string $neighborhood,
        public string $city,
        public string $state,
        public string $zipCode,
        public ?string $country,
    ) {}

    /**
     * @param  array{street: string, number: string, complement?: ?string, neighborhood: string, city: string, state: string, zip_code: string, country?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            street: $data['street'],
            number: $data['number'],
            complement: $data['complement'] ?? null,
            neighborhood: $data['neighborhood'],
            city: $data['city'],
            state: $data['state'],
            zipCode: $data['zip_code'],
            country: $data['country'] ?? null,
        );
    }

    /**
     * @return array{street: string, number: string, complement: ?string, neighborhood: string, city: string, state: string, zip_code: string, country: ?string}
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
            'country' => $this->country,
        ];
    }
}
