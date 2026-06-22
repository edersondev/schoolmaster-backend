<?php

declare(strict_types=1);

namespace App\DTOs\Questionnaires;

final readonly class CreateQuestionnaireData
{
    public function __construct(
        public string $title,
        public array $questions,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['title'], $data['questions'], $data['description'] ?? null);
    }
}
