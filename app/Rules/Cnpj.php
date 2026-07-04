<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (! self::isValid($digits)) {
            $fail('The :attribute field must be a valid CNPJ.');
        }
    }

    public static function isValid(string $digits): bool
    {
        if (! preg_match('/^[0-9]{14}$/', $digits)) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        return self::checkDigit(substr($digits, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === (int) $digits[12]
            && self::checkDigit(substr($digits, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === (int) $digits[13];
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
