<?php

declare(strict_types=1);

namespace App\DTOs\Assessment;

final readonly class AssessmentQuestionSchema
{
    public function __construct(
        public string $questionType,
        public ?array $answerSchema,
        public ?array $gradingRule,
        public ?array $visibility,
    ) {}

    public static function fromArray(array $question): self
    {
        return new self(
            questionType: (string) $question['question_type'],
            answerSchema: $question['answer_schema'] ?? null,
            gradingRule: $question['grading_rule'] ?? null,
            visibility: $question['visibility'] ?? null,
        );
    }
}
