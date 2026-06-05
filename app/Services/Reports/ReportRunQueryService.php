<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportRun;
use App\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReportRunQueryService
{
    public function listForSchool(School $school, array $filters): LengthAwarePaginator
    {
        $runs = ReportRun::query()
            ->with(['school', 'requester', 'outputs', 'sourceReportRun', 'supersededByReportRun'])
            ->where('school_id', $school->id);

        if ((bool) ($filters['include_deleted'] ?? false)) {
            $runs->withTrashed();
        }

        if (isset($filters['report_type'])) {
            $runs->where('report_type', $filters['report_type']);
        }

        if (isset($filters['generation_status'])) {
            $runs->where('generation_status', $filters['generation_status']);
        }

        if (($filters['report_source'] ?? null) === 'built_in') {
            $runs->whereNull('report_definition_id');
        }

        if (($filters['report_source'] ?? null) === 'custom') {
            $runs->whereNotNull('report_definition_id');
        }

        return $runs->orderByDesc('created_at')->paginate((int) ($filters['per_page'] ?? 25));
    }
}
