<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        $school = School::factory()->create();

        return [
            'school_id' => $school->id,
            'street' => fake()->streetName(),
            'number' => (string) fake()->numberBetween(1, 9999),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->word(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->numerify('########'),
            'country' => fake()->optional()->country(),
            'addressable_type' => School::class,
            'addressable_id' => $school->id,
        ];
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn (): array => [
            'school_id' => $school->id,
            'addressable_type' => School::class,
            'addressable_id' => $school->id,
        ]);
    }
}
