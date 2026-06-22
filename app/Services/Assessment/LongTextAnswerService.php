<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use Illuminate\Validation\ValidationException;

final class LongTextAnswerService
{
    public function normalize(string $answer): string
    {
        if (! mb_check_encoding($answer, 'UTF-8')) {
            throw ValidationException::withMessages([
                'answers' => ['Long-text answers must be valid UTF-8.'],
            ]);
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $answer) === 1) {
            throw ValidationException::withMessages([
                'answers' => ['Long-text answers contain unsafe control characters.'],
            ]);
        }

        if (trim($answer) === '') {
            throw ValidationException::withMessages([
                'answers' => ['Long-text answers cannot be blank.'],
            ]);
        }

        if (mb_strlen($answer) > 10_000) {
            throw ValidationException::withMessages([
                'answers' => ['Long-text answers cannot exceed 10000 characters.'],
            ]);
        }

        return $answer;
    }
}
