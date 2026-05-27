<?php

declare(strict_types=1);

namespace App\DTOs\AdministrationLifecycle;

final readonly class UpdateAdministrationResourceData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(public array $attributes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
