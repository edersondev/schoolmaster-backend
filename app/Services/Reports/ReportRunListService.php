<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\TenantContext;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolReports;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReportRunListService
{
    use AuthorizesSchoolReports;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly ReportFilterValidator $filters,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->filters->validateList($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertReportPermission($actor, $school, 'reports.view');

        $runs = ReportRun::query()
            ->with(['school', 'requester'])
            ->where('school_id', $school->id);

        if (isset($filters['report_type'])) {
            $runs->where('report_type', $filters['report_type']);
        }

        return $runs->orderByDesc('created_at')->paginate((int) ($filters['per_page'] ?? 25));
    }
}
