<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
final class SchoolFactory extends Factory
{
    protected $model = School::class;

    private static int $cnpjSequence = 1;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' School',
            'cnpj' => self::validCnpj(self::$cnpjSequence++),
            'status' => School::STATUS_ACTIVE,
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => School::STATUS_INACTIVE]);
    }

    private static function validCnpj(int $sequence): string
    {
        $base = str_pad((string) $sequence, 8, '0', STR_PAD_LEFT).'0001';
        $firstDigit = self::checkDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = self::checkDigit($base.$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $base.$firstDigit.$secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private static function checkDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
