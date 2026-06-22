<?php

declare(strict_types=1);

namespace App\DTOs\Assessment;

final readonly class AssessmentQuestionInput
{
    public function __construct(
        public string $questionType,
        public string $prompt,
        public int $sequence,
        public ?array $options = null,
        public ?string $correctAnswer = null,
        public ?array $answerSchema = null,
        public ?array $gradingRule = null,
        public ?array $visibility = null,
    ) {}

    public static function fromArray(array $question): self
    {
        return new self(
            questionType: (string) $question['question_type'],
            prompt: (string) $question['prompt'],
            sequence: (int) $question['sequence'],
            options: $question['options'] ?? null,
            correctAnswer: $question['correct_answer'] ?? null,
            answerSchema: $question['answer_schema'] ?? null,
            gradingRule: $question['grading_rule'] ?? null,
            visibility: $question['visibility'] ?? null,
        );
    }

    public function toPersistenceArray(): array
    {
        return [
            'question_type' => $this->questionType,
            'prompt' => $this->prompt,
            'options' => $this->options,
            'correct_answer' => $this->correctAnswer,
            'answer_schema' => $this->answerSchema,
            'grading_rule' => $this->gradingRule,
            'visibility' => $this->visibility,
            'sequence' => $this->sequence,
        ];
    }
}
