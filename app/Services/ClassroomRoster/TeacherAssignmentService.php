<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\ClassroomRosterPolicy;
use App\Repositories\ClassroomRoster\ClassroomRosterLookupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TeacherAssignmentService
{
    public function __construct(
        private SchoolContextGuard $schoolContextGuard,
        private ClassroomRosterPolicy $policy,
        private EffectiveDateValidator $effectiveDates,
        private ClassroomRosterLookupRepository $lookups,
        private RosterAuditLogger $auditLogger,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $isAdmin = $this->policy->manage($actor, $school);

        return TeacherAssignment::query()
            ->with(['school', 'classSection', 'teacher', 'academicPeriod', 'creator', 'updater'])
            ->where('school_id', $school->id)
            ->when(! $isAdmin, fn ($builder) => $builder->where('teacher_user_id', $actor->id)->where('status', 'active'))
            ->when($query['academicPeriodId'] ?? null, fn ($builder, string $periodUuid) => $builder->where('academic_period_id', $this->periodId($periodUuid, $school)))
            ->when($query['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy('created_at')
            ->paginate((int) ($query['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, array $data): TeacherAssignment
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $teacher = User::query()->where('uuid', $data['teacher_user_id'])->where('school_id', $school->id)->first();

        if ($teacher === null || $teacher->status !== 'active' || ! $teacher->hasSchoolPermission('learning_sets.manage', $school->id)) {
            throw ValidationException::withMessages(['teacher_user_id' => ['The teacher must be active in the school and teacher-compatible on the effective start date.']]);
        }

        $effectiveDate = CarbonImmutable::parse($data['effective_start_date'])->startOfDay();

        try {
            return DB::transaction(function () use ($actor, $school, $teacher, $data, $effectiveDate): TeacherAssignment {
                $classSection = $this->lockActiveClassSection($data['class_section_id'], $school);
                $period = $classSection->academicPeriod;

                if ($period->uuid !== $data['academic_period_id'] || $period->status !== 'active') {
                    throw ValidationException::withMessages(['academic_period_id' => ['The academic period is not compatible with the class section.']]);
                }

                $this->effectiveDates->assertUsable(new EffectiveDateInput($school, $period, $effectiveDate, 'effective_start_date'));
                $this->assertNoDuplicate($classSection, $teacher, $period->id);

                $assignment = TeacherAssignment::query()->create([
                    'school_id' => $school->id,
                    'class_section_id' => $classSection->id,
                    'teacher_user_id' => $teacher->id,
                    'academic_period_id' => $period->id,
                    'status' => 'active',
                    'effective_start_date' => $effectiveDate->toDateString(),
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);

                $this->auditLogger->record('teacher_assignment.created', 'succeeded', $school, $actor, 'teacher_assignment', $assignment->uuid, metadata: [
                    'academic_period_id' => $period->uuid,
                    'status' => 'active',
                ]);

                return $assignment->load(['school', 'classSection', 'teacher', 'academicPeriod', 'creator', 'updater']);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateConstraint($exception)) {
                throw new ConflictException('An active teacher assignment already exists for this teacher and class section.');
            }

            throw $exception;
        }
    }

    public function get(User $actor, TenantContext $context, string $uuid): TeacherAssignment
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $assignment = TeacherAssignment::query()
            ->with(['school', 'classSection', 'teacher', 'academicPeriod', 'creator', 'updater'])
            ->where('uuid', $uuid)
            ->where('school_id', $school->id)
            ->first();

        if ($assignment === null) {
            abort(404);
        }

        if (! $this->policy->manage($actor, $school) && ! $this->policy->viewOwnActiveTeacherAssignment($actor, $assignment->teacher_user_id, $assignment->status)) {
            throw new AuthorizationException('Teachers may read only their own active assignments.');
        }

        return $assignment;
    }

    public function deactivate(User $actor, TenantContext $context, string $uuid, array $data): TeacherAssignment
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $assignment = $this->get($actor, $context, $uuid);

        if ($assignment->status !== 'active') {
            throw new ConflictException('Only active teacher assignments can be deactivated.');
        }

        $effectiveDate = CarbonImmutable::parse($data['effective_end_date'])->startOfDay();
        $this->effectiveDates->assertUsable(new EffectiveDateInput($school, $assignment->academicPeriod, $effectiveDate, 'effective_end_date'));
        $this->effectiveDates->assertEndingDateNotBeforeStart($effectiveDate, CarbonImmutable::parse($assignment->effective_start_date), 'effective_end_date');

        return DB::transaction(function () use ($actor, $school, $assignment, $effectiveDate, $data): TeacherAssignment {
            $assignment->update([
                'status' => 'inactive',
                'effective_end_date' => $effectiveDate->toDateString(),
                'deactivation_reason' => $data['reason'],
                'updated_by_user_id' => $actor->id,
            ]);

            $this->auditLogger->record('teacher_assignment.deactivated', 'succeeded', $school, $actor, 'teacher_assignment', $assignment->uuid, $data['reason'], [
                'target_status' => 'inactive',
            ]);

            return $assignment->refresh()->load(['school', 'classSection', 'teacher', 'academicPeriod', 'creator', 'updater']);
        });
    }

    private function authorizeManage(User $actor, School $school): void
    {
        if (! $this->policy->manage($actor, $school)) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }

    private function classSection(string $uuid, School $school): ClassSection
    {
        $classSection = ClassSection::query()->with(['school', 'academicPeriod'])->where('uuid', $uuid)->where('school_id', $school->id)->first();

        if ($classSection === null) {
            throw ValidationException::withMessages(['class_section_id' => ['The class section was not found in the resolved school.']]);
        }

        if ($classSection->status !== 'active') {
            throw new ConflictException('Teacher assignments require an active class section.');
        }

        return $classSection;
    }

    private function lockActiveClassSection(string $uuid, School $school): ClassSection
    {
        $classSection = ClassSection::query()
            ->with(['school', 'academicPeriod'])
            ->where('uuid', $uuid)
            ->where('school_id', $school->id)
            ->lockForUpdate()
            ->first();

        if ($classSection === null) {
            throw ValidationException::withMessages(['class_section_id' => ['The class section was not found in the resolved school.']]);
        }

        if ($classSection->status !== 'active') {
            throw new ConflictException('Teacher assignments require an active class section.');
        }

        return $classSection;
    }

    private function periodId(string $periodUuid, School $school): int
    {
        $period = $this->lookups->academicPeriodForSchool($periodUuid, $school->id);

        if ($period === null) {
            throw ValidationException::withMessages(['academicPeriodId' => ['The academic period was not found in the resolved school.']]);
        }

        return $period->id;
    }

    private function assertNoDuplicate(ClassSection $classSection, User $teacher, int $periodId): void
    {
        $exists = TeacherAssignment::query()
            ->where('class_section_id', $classSection->id)
            ->where('teacher_user_id', $teacher->id)
            ->where('academic_period_id', $periodId)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            throw new ConflictException('An active teacher assignment already exists for this teacher and class section.');
        }
    }

    private function isDuplicateConstraint(QueryException $exception): bool
    {
        return str_contains((string) $exception->getMessage(), 'teacher_assignments_active_unique')
            || $exception->getCode() === '23000';
    }
}
