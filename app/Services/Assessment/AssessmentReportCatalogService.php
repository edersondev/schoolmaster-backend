<?php

declare(strict_types=1);

namespace App\Services\Assessment;

final class AssessmentReportCatalogService
{
    public function __construct(private readonly AssessmentResponseVisibilityService $visibility) {}

    public function domain(): array
    {
        $fields = array_map(
            fn (string $field): array => ['id' => $field, 'label' => str_replace('_', ' ', $field), 'visibility' => 'aggregate_only'],
            $this->visibility->reportSafeFields(),
        );

        return [
            'id' => 'advanced_assessments',
            'label' => 'advanced assessments',
            'fields' => $fields,
            'filters' => [
                ['id' => 'academic_period_id', 'operators' => ['equals'], 'reference_type' => 'academic_period'],
                ['id' => 'student_profile_id', 'operators' => ['equals'], 'reference_type' => 'student_profile'],
                ['id' => 'grading_status', 'operators' => ['equals'], 'reference_type' => null],
            ],
            'grouping' => ['assessment_completion_status', 'assessment_grading_status'],
            'sorting' => ['assessment_response_count', 'assessment_score_summary'],
            'output_formats' => ['pdf', 'csv', 'xlsx'],
        ];
    }
}
