<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\ClassSection;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\ClassroomRosterPolicy;
use App\Repositories\ClassroomRoster\ClassroomRosterLookupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RosterMembershipService
{
    public function __construct(
        private SchoolContextGuard $schoolContextGuard,
        private ClassroomRosterPolicy $policy,
        private ClassroomRosterLookupRepository $lookups,
        private EffectiveDateValidator $effectiveDates,
        private RosterAuditLogger $auditLogger,
    ) {}

    public function list(User $actor, TenantContext $context, string $classSectionUuid, array $query): LengthAwarePaginator
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $classSection = $this->classSection($classSectionUuid, $school);

        return RosterMembership::query()
            ->with(['school', 'classSection', 'studentProfile', 'academicPeriod', 'creator', 'ender'])
            ->where('school_id', $school->id)
            ->where('class_section_id', $classSection->id)
            ->when($query['academicPeriodId'] ?? null, fn ($builder, string $periodUuid) => $builder->where('academic_period_id', $this->periodId($periodUuid, $school)))
            ->when($query['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy('created_at')
            ->paginate((int) ($query['per_page'] ?? 25));
    }

    public function addBatch(User $actor, TenantContext $context, string $classSectionUuid, array $data): array
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $classSection = $this->classSection($classSectionUuid, $school);
        $period = $this->lookups->academicPeriodForSchool($data['academic_period_id'], $school->id);

        if ($period === null || $period->id !== $classSection->academic_period_id || $period->status !== 'active') {
            throw ValidationException::withMessages(['academic_period_id' => ['The academic period is not compatible with the class section.']]);
        }

        $effectiveDate = CarbonImmutable::parse($data['effective_start_date'])->startOfDay();
        $this->effectiveDates->assertUsable(new EffectiveDateInput($school, $period, $effectiveDate, 'effective_start_date'));
        $students = $this->students($data['student_profile_ids'], $school, $effectiveDate);
        $this->assertNoActiveMemberships($classSection, $period->id, $students);

        return DB::transaction(function () use ($actor, $school, $classSection, $period, $effectiveDate, $students): array {
            $memberships = [];

            foreach ($students as $student) {
                $memberships[] = RosterMembership::query()->create([
                    'school_id' => $school->id,
                    'class_section_id' => $classSection->id,
                    'student_profile_id' => $student->id,
                    'academic_period_id' => $period->id,
                    'status' => 'active',
                    'effective_start_date' => $effectiveDate->toDateString(),
                    'created_by_user_id' => $actor->id,
                ])->load(['school', 'classSection', 'studentProfile', 'academicPeriod', 'creator', 'ender']);
            }

            $this->auditLogger->record('roster_memberships.added', 'succeeded', $school, $actor, 'class_section', $classSection->uuid, metadata: [
                'academic_period_id' => $period->uuid,
                'batch_size' => count($students),
                'status' => 'active',
            ]);

            return $memberships;
        });
    }

    public function endBatch(User $actor, TenantContext $context, string $classSectionUuid, array $data): array
    {
        $school = $this->schoolContextGuard->requireResolved($context);
        $this->authorizeManage($actor, $school);
        $classSection = $this->classSection($classSectionUuid, $school);
        $memberships = RosterMembership::query()
            ->with(['school', 'classSection', 'studentProfile', 'academicPeriod', 'creator', 'ender'])
            ->where('school_id', $school->id)
            ->where('class_section_id', $classSection->id)
            ->whereIn('uuid', $data['roster_membership_ids'])
            ->get();

        if ($memberships->count() !== count($data['roster_membership_ids'])) {
            throw ValidationException::withMessages(['roster_membership_ids' => ['Every membership must exist in the resolved class section.']]);
        }

        $effectiveDate = CarbonImmutable::parse($data['effective_end_date'])->startOfDay();

        foreach ($memberships as $membership) {
            if ($membership->status !== 'active') {
                throw new ConflictException('Only active memberships can be ended.');
            }

            $this->effectiveDates->assertUsable(new EffectiveDateInput($school, $membership->academicPeriod, $effectiveDate, 'effective_end_date'));
            $this->effectiveDates->assertEndingDateNotBeforeStart($effectiveDate, CarbonImmutable::parse($membership->effective_start_date), 'effective_end_date');
        }

        return DB::transaction(function () use ($actor, $school, $memberships, $effectiveDate, $data): array {
            foreach ($memberships as $membership) {
                $membership->update([
                    'status' => 'ended',
                    'effective_end_date' => $effectiveDate->toDateString(),
                    'end_reason' => $data['reason'],
                    'ended_by_user_id' => $actor->id,
                ]);
            }

            $this->auditLogger->record('roster_memberships.ended', 'succeeded', $school, $actor, 'class_section', $memberships->first()->classSection->uuid, $data['reason'], [
                'batch_size' => $memberships->count(),
                'target_status' => 'ended',
            ]);

            return $memberships->map->refresh()->map->load(['school', 'classSection', 'studentProfile', 'academicPeriod', 'creator', 'ender'])->all();
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
            abort(404);
        }

        if ($classSection->status !== 'active') {
            throw new ConflictException('Class section must be active for roster membership changes.');
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

    private function students(array $studentUuids, School $school, CarbonImmutable $effectiveDate): array
    {
        $students = StudentProfile::query()->where('school_id', $school->id)->whereIn('uuid', $studentUuids)->get();

        if ($students->count() !== count($studentUuids)) {
            throw ValidationException::withMessages(['student_profile_ids' => ['Every student profile must exist in the resolved school.']]);
        }

        foreach ($students as $student) {
            if ($student->status !== 'active' || $student->trashed() || $student->enrolled_at === null || CarbonImmutable::parse($student->enrolled_at)->isAfter($effectiveDate)) {
                throw ValidationException::withMessages(['student_profile_ids' => ['Every student must be active and enrolled on the effective start date.']]);
            }
        }

        return $students->all();
    }

    private function assertNoActiveMemberships(ClassSection $classSection, int $periodId, array $students): void
    {
        $studentIds = array_map(fn (StudentProfile $student): int => $student->id, $students);
        $exists = RosterMembership::query()
            ->where('class_section_id', $classSection->id)
            ->where('academic_period_id', $periodId)
            ->where('status', 'active')
            ->whereIn('student_profile_id', $studentIds)
            ->exists();

        if ($exists) {
            throw new ConflictException('An active roster membership already exists for at least one requested student.');
        }
    }
}
