<?php

declare(strict_types=1);

namespace App\DTOs\Assessment;

final readonly class AssessmentResponseSubmissionData
{
    /**
     * @param  list<AssessmentAnswerInput>  $answers
     */
    public function __construct(
        public string $questionnaireId,
        public string $learningSetId,
        public array $answers,
    ) {}

    /**
     * @param  array{questionnaire_id:string, learning_set_id:string, answers:list<array<string, mixed>>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            questionnaireId: $data['questionnaire_id'],
            learningSetId: $data['learning_set_id'],
            answers: array_map(
                fn (array $answer): AssessmentAnswerInput => AssessmentAnswerInput::fromArray($answer),
                $data['answers'],
            ),
        );
    }
}
