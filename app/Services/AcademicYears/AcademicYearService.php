<?php

declare(strict_types=1);

namespace App\Services\AcademicYears;

use App\DTOs\AcademicYears\CreateAcademicYearData;
use App\DTOs\TenantContext;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class AcademicYearService
{
    use AuthorizesSchoolAdministration;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'academic_years.view');

        return AcademicYear::query()
            ->with('school')
            ->where('school_id', $school->id)
            ->when($query['name'] ?? null, fn (Builder $academicYears, string $name): Builder => $this->whereNameContains($academicYears, $name))
            ->when($query['date_from'] ?? null, fn (Builder $academicYears, string $dateFrom): Builder => $academicYears->where('end_date', '>=', $dateFrom))
            ->when($query['date_to'] ?? null, fn (Builder $academicYears, string $dateTo): Builder => $academicYears->where('start_date', '<=', $dateTo))
            ->when($query['status'] ?? null, fn (Builder $academicYears, string $status): Builder => $academicYears->where('status', $status))
            ->orderByDesc('start_date')
            ->paginate((int) ($query['per_page'] ?? 25));
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

    /**
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    private function whereNameContains(Builder $query, string $name): Builder
    {
        $escaped = addcslashes(mb_strtolower($name), '\\%_');

        return $query->whereRaw(
            sprintf('LOWER(%s) COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE ?', $query->getQuery()->getGrammar()->wrap('name')),
            ['%'.$escaped.'%', '\\'],
        );
    }
}
