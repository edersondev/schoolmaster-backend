<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\AcademicPeriod;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\User;
use App\Policies\ClassroomRosterPolicy;
use App\Repositories\ClassroomRoster\ClassroomRosterLookupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ClassSectionService
{
    public function __construct(
        private SchoolContextGuard $schoolContextGuard,
        private ClassroomRosterPolicy $policy,
        private EffectiveDateValidator $effectiveDates,
        private ClassroomRosterLookupRepository $lookups,
        private RosterAuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);

        return ClassSection::query()
            ->with(['school', 'academicPeriod', 'creator', 'updater'])
            ->where('school_id', $school->id)
            ->when($query['academicPeriodId'] ?? null, function ($builder, string $periodUuid) use ($school): void {
                $period = $this->compatibleAcademicPeriod($periodUuid, $school);
                $builder->where('academic_period_id', $period->id);
            })
            ->when($query['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy('code')
            ->paginate((int) ($query['per_page'] ?? 25));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, TenantContext $context, array $data): ClassSection
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $this->assertMetadataShape($data);
        $period = $this->compatibleAcademicPeriod($data['academic_period_id'], $school);

        $this->assertUniqueCode($school, $period, $data['code']);

        try {
            return DB::transaction(function () use ($actor, $school, $period, $data): ClassSection {
                $classSection = ClassSection::query()->create([
                    'school_id' => $school->id,
                    'academic_period_id' => $period->id,
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'course_metadata' => $data['course'] ?? null,
                    'classroom_metadata' => $data['classroom'] ?? null,
                    'section_metadata' => $data['section'] ?? null,
                    'group_metadata' => $data['group'] ?? null,
                    'status' => 'active',
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);

                $this->auditLogger->record(
                    action: 'class_section.created',
                    outcome: 'succeeded',
                    school: $school,
                    actor: $actor,
                    targetType: 'class_section',
                    targetUuid: $classSection->uuid,
                    metadata: [
                        'academic_period_id' => $period->uuid,
                        'code' => $classSection->code,
                        'status' => $classSection->status,
                    ],
                );

                return $classSection->load(['school', 'academicPeriod', 'creator', 'updater']);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateConstraint($exception)) {
                throw new ConflictException('A class section with this code already exists for the academic period.');
            }

            throw $exception;
        }
    }

    public function get(User $actor, TenantContext $context, string $classSectionUuid): ClassSection
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);

        /** @var ClassSection|null $classSection */
        $classSection = ClassSection::query()
            ->with(['school', 'academicPeriod', 'creator', 'updater'])
            ->where('uuid', $classSectionUuid)
            ->where('school_id', $school->id)
            ->first();

        if ($classSection === null) {
            abort(404);
        }

        return $classSection;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, TenantContext $context, string $classSectionUuid, array $data): ClassSection
    {
        $classSection = $this->get($actor, $context, $classSectionUuid);
        $this->assertMetadataShape($data);

        if (isset($data['code']) && $data['code'] !== $classSection->code) {
            $this->assertUniqueCode($classSection->school, $classSection->academicPeriod, $data['code'], $classSection->id);
        }

        try {
            return DB::transaction(function () use ($actor, $classSection, $data): ClassSection {
                $classSection->fill([
                    ...array_intersect_key($data, array_flip(['code', 'name'])),
                    'course_metadata' => array_key_exists('course', $data) ? $data['course'] : $classSection->course_metadata,
                    'classroom_metadata' => array_key_exists('classroom', $data) ? $data['classroom'] : $classSection->classroom_metadata,
                    'section_metadata' => array_key_exists('section', $data) ? $data['section'] : $classSection->section_metadata,
                    'group_metadata' => array_key_exists('group', $data) ? $data['group'] : $classSection->group_metadata,
                    'updated_by_user_id' => $actor->id,
                ])->save();

                $this->auditLogger->record(
                    action: 'class_section.updated',
                    outcome: 'succeeded',
                    school: $classSection->school,
                    actor: $actor,
                    targetType: 'class_section',
                    targetUuid: $classSection->uuid,
                    metadata: [
                        'academic_period_id' => $classSection->academicPeriod->uuid,
                        'code' => $classSection->code,
                        'status' => $classSection->status,
                    ],
                );

                return $classSection->refresh()->load(['school', 'academicPeriod', 'creator', 'updater']);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateConstraint($exception)) {
                throw new ConflictException('A class section with this code already exists for the academic period.');
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(User $actor, TenantContext $context, string $classSectionUuid, array $data): ClassSection
    {
        $classSection = $this->get($actor, $context, $classSectionUuid);
        $effectiveDate = CarbonImmutable::parse($data['effective_at'])->startOfDay();

        return DB::transaction(function () use ($actor, $classSection, $data, $effectiveDate): ClassSection {
            $classSection = ClassSection::query()
                ->with(['school', 'academicPeriod', 'creator', 'updater'])
                ->whereKey($classSection->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($classSection->status !== 'active') {
                throw new ConflictException('Inactive class sections cannot be reactivated or transitioned again in v1.');
            }

            $this->effectiveDates->assertUsable(new EffectiveDateInput(
                school: $classSection->school,
                academicPeriod: $classSection->academicPeriod,
                effectiveDate: $effectiveDate,
                field: 'effective_at',
            ));

            if ($this->lookups->hasActiveRosterMemberships($classSection->id) || $this->lookups->hasActiveTeacherAssignments($classSection->id)) {
                throw new ConflictException('Class section cannot be inactivated while active memberships or teacher assignments exist.');
            }

            $classSection->update([
                'status' => 'inactive',
                'inactive_reason' => $data['reason'],
                'inactive_effective_at' => $effectiveDate->toDateString(),
                'updated_by_user_id' => $actor->id,
            ]);

            $this->auditLogger->record(
                action: 'class_section.inactivated',
                outcome: 'succeeded',
                school: $classSection->school,
                actor: $actor,
                targetType: 'class_section',
                targetUuid: $classSection->uuid,
                reason: $data['reason'],
                metadata: [
                    'academic_period_id' => $classSection->academicPeriod->uuid,
                    'code' => $classSection->code,
                    'target_status' => 'inactive',
                ],
            );

            return $classSection->refresh()->load(['school', 'academicPeriod', 'creator', 'updater']);
        });
    }

    private function authorizeManage(User $actor, School $school): void
    {
        if (! $this->policy->manage($actor, $school)) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }

    private function compatibleAcademicPeriod(string $academicPeriodUuid, School $school): AcademicPeriod
    {
        $period = $this->lookups->academicPeriodForSchool($academicPeriodUuid, $school->id);

        if ($period === null) {
            throw ValidationException::withMessages(['academic_period_id' => ['The academic period was not found in the resolved school.']]);
        }

        if ($period->status !== 'active' || $period->start_date === null || $period->end_date === null) {
            throw ValidationException::withMessages(['academic_period_id' => ['The academic period must be active and compatible with roster operations.']]);
        }

        return $period;
    }

    private function assertUniqueCode(School $school, AcademicPeriod $period, string $code, ?int $ignoreId = null): void
    {
        $duplicate = ClassSection::query()
            ->where('school_id', $school->id)
            ->where('academic_period_id', $period->id)
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($duplicate) {
            throw new ConflictException('A class section with this code already exists for the academic period.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMetadataShape(array $data): void
    {
        foreach (['course', 'classroom', 'section', 'group'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $extraKeys = array_diff(array_keys((array) $data[$field]), ['code', 'name']);

            if ($extraKeys !== []) {
                throw ValidationException::withMessages([
                    $field => ['Metadata blocks may include only code and name.'],
                ]);
            }
        }
    }

    private function isDuplicateConstraint(QueryException $exception): bool
    {
        return str_contains((string) $exception->getMessage(), 'class_sections_school_period_code_unique')
            || $exception->getCode() === '23000';
    }
}
