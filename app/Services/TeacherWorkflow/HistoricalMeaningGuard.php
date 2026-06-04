<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\Exceptions\ConflictException;
use App\Models\Questionnaire;
use App\Models\TeacherContentItem;

final class HistoricalMeaningGuard
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function assertContentEditable(TeacherContentItem $content, array $changes): void
    {
        if (! $this->hasStudentFacingContentChanges($changes, ['title', 'description']) || ! $this->contentIsUsed($content)) {
            return;
        }

        throw new ConflictException('Content historical meaning cannot be changed after student-facing use in v1.');
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function assertQuestionnaireEditable(Questionnaire $questionnaire, array $changes): void
    {
        if (! array_key_exists('questions', $changes) || ! $this->questionnaireIsUsed($questionnaire)) {
            return;
        }

        throw new ConflictException('Questionnaire historical meaning cannot be changed after student-facing use in v1.');
    }

    public function contentIsUsed(TeacherContentItem $content): bool
    {
        return $content->learningSetEntries()
            ->whereHas('learningSet', fn ($query) => $query
                ->whereIn('status', ['published', 'active'])
                ->orWhereHas('assignments'))
            ->exists();
    }

    public function questionnaireIsUsed(Questionnaire $questionnaire): bool
    {
        return $questionnaire->learningSetEntries()
            ->whereHas('learningSet', fn ($query) => $query
                ->whereIn('status', ['published', 'active'])
                ->orWhereHas('assignments'))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<int, string>  $fields
     */
    private function hasStudentFacingContentChanges(array $changes, array $fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $changes)) {
                return true;
            }
        }

        return false;
    }
}
