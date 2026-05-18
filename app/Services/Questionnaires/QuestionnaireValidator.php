<?php

declare(strict_types=1);

namespace App\Services\Questionnaires;

use Illuminate\Validation\ValidationException;

final class QuestionnaireValidator
{
    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function validate(array $questions): void
    {
        $sequences = [];

        foreach ($questions as $index => $question) {
            $extra = array_diff(array_keys($question), ['question_type', 'prompt', 'options', 'correct_answer', 'sequence']);
            if ($extra !== []) {
                throw ValidationException::withMessages(["questions.$index" => ['Question contains undocumented fields.']]);
            }

            if ($question['question_type'] === 'multiple_choice' && count($question['options'] ?? []) < 2) {
                throw ValidationException::withMessages(["questions.$index.options" => ['Multiple choice questions require at least two options.']]);
            }

            if (in_array($question['sequence'], $sequences, true)) {
                throw ValidationException::withMessages(['questions' => ['Question sequences must be unique.']]);
            }

            $sequences[] = $question['sequence'];
        }
    }
}
