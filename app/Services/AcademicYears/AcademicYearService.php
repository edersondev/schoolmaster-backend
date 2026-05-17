<?php

declare(strict_types=1);

namespace App\Services\AcademicYears;

use App\DTOs\AcademicYears\CreateAcademicYearData;
use App\DTOs\TenantContext;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AcademicYearService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQuery($query, allowedStatuses: ['planned', 'active', 'closed', 'inactive']);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'academic_years.view');

        return AcademicYear::query()
            ->with('school')
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('start_date')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateAcademicYearData $data): AcademicYear
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'academic_years.manage');

        return AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => $data->name,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'status' => 'planned',
        ])->load('school');
    }
}
