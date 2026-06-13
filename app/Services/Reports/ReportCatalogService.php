<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportActorContext;
use App\Services\Assessment\AssessmentReportCatalogService;

final class ReportCatalogService
{
    public function __construct(private readonly AssessmentReportCatalogService $assessments) {}

    public function catalog(ReportActorContext $context): array
    {
        if (! $context->actor->hasSchoolPermission('reports.definitions.manage', $context->school->id)) {
            abort(403, 'The authenticated user lacks permission for this action.');
        }

        return [
            'domains' => [
                $this->domain('attendance', ['student_name', 'attendance_status', 'attendance_date', 'academic_period_name'], ['academic_period_id', 'student_profile_id', 'status', 'start_date', 'end_date'], ['pdf', 'csv', 'xlsx']),
                $this->domain('grades', ['student_name', 'grade_value', 'subject', 'academic_period_name'], ['academic_period_id', 'student_profile_id', 'status'], ['pdf', 'csv', 'xlsx']),
                $this->domain('academic_structure', ['academic_year_name', 'academic_period_name', 'status'], ['academic_period_id', 'status'], ['pdf', 'csv']),
                $this->domain('school_activity', ['user_name', 'activity_type', 'activity_date'], ['user_id', 'start_date', 'end_date'], ['pdf', 'csv']),
                $this->assessments->domain(),
            ],
            'complexity_limits' => [
                'max_fields' => 25,
                'max_filters' => 10,
                'max_grouping_levels' => 2,
                'max_sort_fields' => 3,
            ],
        ];
    }

    private function domain(string $id, array $fields, array $filters, array $formats): array
    {
        return [
            'id' => $id,
            'label' => str_replace('_', ' ', $id),
            'fields' => array_map(fn (string $field): array => ['id' => $field, 'label' => str_replace('_', ' ', $field), 'visibility' => 'operational'], $fields),
            'filters' => array_map(fn (string $filter): array => ['id' => $filter, 'operators' => ['equals']], $filters),
            'grouping' => $fields,
            'sorting' => $fields,
            'output_formats' => $formats,
        ];
    }
}
