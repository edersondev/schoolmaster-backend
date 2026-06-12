<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentQuestionSchema;
use Illuminate\Validation\ValidationException;

final class AssessmentQuestionSchemaService
{
    public const QUESTION_TYPES = ['multiple_choice', 'true_false', 'short_text', 'long_text', 'file_response'];

    public function validate(AssessmentQuestionSchema $schema, string $field): void
    {
        if (! in_array($schema->questionType, self::QUESTION_TYPES, true)) {
            throw ValidationException::withMessages([$field.'.question_type' => ['Question type is not supported.']]);
        }

        match ($schema->questionType) {
            'long_text' => $this->validateLongText($schema, $field),
            'file_response' => $this->validateFileResponse($schema, $field),
            default => $this->validateLegacy($schema, $field),
        };
    }

    private function validateLegacy(AssessmentQuestionSchema $schema, string $field): void
    {
        if ($schema->answerSchema !== null || $schema->gradingRule !== null || $schema->visibility !== null) {
            throw ValidationException::withMessages([$field => ['Legacy question types cannot include advanced assessment schema fields.']]);
        }
    }

    private function validateLongText(AssessmentQuestionSchema $schema, string $field): void
    {
        $answerSchema = $schema->answerSchema ?? [];
        $this->assertAllowedKeys($answerSchema, ['min_length', 'max_length'], $field.'.answer_schema');
        $this->assertAllowedKeys($schema->gradingRule ?? [], ['mode', 'allow_exempt'], $field.'.grading_rule');
        $this->assertAllowedKeys($schema->visibility ?? [], ['student_answer_visible', 'report_visibility'], $field.'.visibility');
        $min = $answerSchema['min_length'] ?? 1;
        $max = $answerSchema['max_length'] ?? 10000;

        if ($min !== 1 || $max !== 10000) {
            throw ValidationException::withMessages([$field.'.answer_schema' => ['Long-text questions must use the fixed 1-10000 character bounds.']]);
        }

        $this->assertManualGrading($schema, $field);
    }

    private function validateFileResponse(AssessmentQuestionSchema $schema, string $field): void
    {
        $answerSchema = $schema->answerSchema ?? [];
        $this->assertAllowedKeys($answerSchema, ['allowed_file_categories', 'max_file_size_bytes', 'max_files'], $field.'.answer_schema');
        $this->assertAllowedKeys($schema->gradingRule ?? [], ['mode', 'allow_exempt'], $field.'.grading_rule');
        $this->assertAllowedKeys($schema->visibility ?? [], ['student_answer_visible', 'report_visibility'], $field.'.visibility');
        $categories = $answerSchema['allowed_file_categories'] ?? ['pdf', 'image', 'text', 'office'];
        sort($categories);

        if ($categories !== ['image', 'office', 'pdf', 'text']
            || ($answerSchema['max_file_size_bytes'] ?? 26214400) !== 26214400
            || ($answerSchema['max_files'] ?? 1) !== 1) {
            throw ValidationException::withMessages([$field.'.answer_schema' => ['File-response questions must use approved file categories, 25 MB limit, and one file.']]);
        }

        $this->assertManualGrading($schema, $field);
    }

    private function assertManualGrading(AssessmentQuestionSchema $schema, string $field): void
    {
        $mode = $schema->gradingRule['mode'] ?? 'manual_0_100';

        if ($mode !== 'manual_0_100') {
            throw ValidationException::withMessages([$field.'.grading_rule' => ['Advanced questions require manual_0_100 grading.']]);
        }

        $visibility = $schema->visibility['report_visibility'] ?? 'summary_only';

        if ($visibility !== 'summary_only') {
            throw ValidationException::withMessages([$field.'.visibility' => ['Advanced assessment report visibility must be summary_only.']]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $allowed
     */
    private function assertAllowedKeys(array $payload, array $allowed, string $field): void
    {
        $extra = array_diff(array_keys($payload), $allowed);

        if ($extra !== []) {
            throw ValidationException::withMessages([$field => ['Advanced assessment schema contains undocumented fields.']]);
        }
    }
}
