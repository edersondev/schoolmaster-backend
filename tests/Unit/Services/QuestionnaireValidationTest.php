<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Questionnaires\QuestionnaireValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class QuestionnaireValidationTest extends TestCase
{
    public function test_rejects_multiple_choice_with_less_than_two_options(): void
    {
        $this->expectException(ValidationException::class);

        (new QuestionnaireValidator)->validate([
            ['question_type' => 'multiple_choice', 'prompt' => 'Pick', 'options' => ['A'], 'sequence' => 1],
        ]);
    }
}
