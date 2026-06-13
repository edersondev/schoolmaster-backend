<?php

declare(strict_types=1);

namespace Tests\Unit\Assessment;

use App\DTOs\Assessment\AssessmentQuestionSchema;
use App\Services\Assessment\AssessmentQuestionSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AssessmentQuestionSchemaValidatorTest extends TestCase
{
    private AssessmentQuestionSchemaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AssessmentQuestionSchemaService;
    }

    public function test_accepts_long_text_defaults(): void
    {
        $this->service->validate(new AssessmentQuestionSchema('long_text', null, null, null), 'questions.0');

        $this->assertTrue(true);
    }

    public function test_rejects_long_text_non_contract_bounds(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate(new AssessmentQuestionSchema('long_text', ['min_length' => 2, 'max_length' => 10000], null, null), 'questions.0');
    }

    public function test_accepts_file_response_contract_rules(): void
    {
        $this->service->validate(new AssessmentQuestionSchema('file_response', [
            'allowed_file_categories' => ['pdf', 'image', 'text', 'office'],
            'max_file_size_bytes' => 26214400,
            'max_files' => 1,
        ], ['mode' => 'manual_0_100'], ['report_visibility' => 'summary_only']), 'questions.0');

        $this->assertTrue(true);
    }

    public function test_rejects_file_response_unsupported_category(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate(new AssessmentQuestionSchema('file_response', [
            'allowed_file_categories' => ['zip'],
            'max_file_size_bytes' => 26214400,
            'max_files' => 1,
        ], ['mode' => 'manual_0_100'], ['report_visibility' => 'summary_only']), 'questions.0');
    }

    public function test_rejects_advanced_schema_on_legacy_question(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate(new AssessmentQuestionSchema('short_text', ['min_length' => 1], null, null), 'questions.0');
    }
}
