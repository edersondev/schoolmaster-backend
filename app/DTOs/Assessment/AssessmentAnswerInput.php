<?php

declare(strict_types=1);

namespace App\DTOs\Assessment;

use Illuminate\Http\UploadedFile;

final readonly class AssessmentAnswerInput
{
    public function __construct(
        public string $questionId,
        public string $questionType,
        public ?string $answerText,
        public ?UploadedFile $file,
    ) {}

    /**
     * @param  array{question_id:string, question_type:string, answer_text?:string|null, file?:UploadedFile|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            questionId: $data['question_id'],
            questionType: $data['question_type'],
            answerText: $data['answer_text'] ?? null,
            file: $data['file'] ?? null,
        );
    }
}
