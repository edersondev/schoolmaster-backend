<?php

declare(strict_types=1);

namespace App\Services\AcademicPeriods;

use App\DTOs\AcademicPeriods\CreateAcademicPeriodData;
use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class AcademicPeriodService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQueryWithAcademicYear($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'academic_periods.view');

        return AcademicPeriod::query()
            ->with(['school', 'academicYear'])
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['academic_year_id'] ?? null, fn ($query, string $academicYearUuid) => $query->where('academic_year_id', $this->resolveAcademicYearId($academicYearUuid, $school->id)))
            ->orderBy('sequence')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateAcademicPeriodData $data): AcademicPeriod
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'academic_periods.manage');

        /** @var AcademicYear|null $academicYear */
        $academicYear = AcademicYear::query()
            ->where('uuid', $data->academicYearId)
            ->where('school_id', $school->id)
            ->first();

        if ($academicYear === null) {
            throw ValidationException::withMessages(['academic_year_id' => ['The academic year was not found in the resolved school.']]);
        }

        $this->assertDateRange($academicYear, $data);

        $duplicateSequence = AcademicPeriod::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('sequence', $data->sequence)
            ->exists();

        if ($duplicateSequence) {
            throw ValidationException::withMessages(['sequence' => ['The sequence must be unique within the academic year.']]);
        }

        return AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => $data->name,
            'sequence' => $data->sequence,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'status' => 'planned',
        ])->load(['school', 'academicYear']);
    }

    private function assertDateRange(AcademicYear $academicYear, CreateAcademicPeriodData $data): void
    {
        $periodStart = Carbon::createFromFormat('Y-m-d', $data->startDate)->startOfDay();
        $periodEnd = Carbon::createFromFormat('Y-m-d', $data->endDate)->startOfDay();

        if ($periodStart->lt($academicYear->start_date) || $periodEnd->gt($academicYear->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => ['The period date range must fit within the academic year.'],
                'end_date' => ['The period date range must fit within the academic year.'],
            ]);
        }
    }

    private function resolveAcademicYearId(string $academicYearUuid, int $schoolId): int
    {
        $academicYear = AcademicYear::query()
            ->where('uuid', $academicYearUuid)
            ->where('school_id', $schoolId)
            ->first();

        if ($academicYear === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => ['The academic year was not found in the resolved school.'],
            ]);
        }

        return $academicYear->id;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function validateListQueryWithAcademicYear(array $query): array
    {
        $allowed = ['page', 'per_page', 'status', 'academic_year_id'];

        foreach (array_keys($query) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages([$field => ['This query parameter is not documented for this request.']]);
            }
        }

        return validator($query, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:planned,active,closed,inactive'],
            'academic_year_id' => ['sometimes', 'uuid'],
        ])->validate();
    }
}
