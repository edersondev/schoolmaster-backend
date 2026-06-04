<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\ClassSection;
use App\Models\LearningSet;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Repositories\TeacherWorkflow\TeacherWorkflowLookupRepository;
use App\Services\LearningSets\LearningSetEntryValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class LearningSetService
{
    public function __construct(
        private readonly SchoolContextGuard $schoolContext,
        private readonly TeacherWorkflowLookupRepository $lookup,
        private readonly LearningSetEntryValidator $entryValidator,
        private readonly LifecycleTransitionService $lifecycle,
        private readonly TeacherWorkflowAuditLogger $audit,
    ) {}

    public function get(User $actor, TenantContext $context, string $uuid): LearningSet
    {
        $learningSet = $this->resolve($context, $uuid);
        Gate::forUser($actor)->authorize('view', $learningSet);

        return $this->load($learningSet);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function update(User $actor, TenantContext $context, string $uuid, array $changes): LearningSet
    {
        $learningSet = $this->resolve($context, $uuid);
        Gate::forUser($actor)->authorize('update', $learningSet);

        return DB::transaction(function () use ($actor, $changes, $learningSet): LearningSet {
            $entryChanges = $changes['entries'] ?? null;
            $rosterAssignment = $changes['roster_assignment'] ?? null;
            unset($changes['entries'], $changes['roster_assignment']);

            if ($changes !== []) {
                $learningSet->forceFill($changes)->save();
            }

            if (is_array($entryChanges)) {
                $entries = $this->entryValidator->validate($entryChanges, $learningSet->school_id);
                $learningSet->entries()->delete();

                foreach ($entries as $entry) {
                    $learningSet->entries()->create([
                        'school_id' => $learningSet->school_id,
                        'entry_type' => $entry['entry_type'],
                        'entry_reference_id' => $entry['entry_reference_id'],
                        'sequence' => $entry['sequence'],
                    ]);
                }
            }

            if (is_array($rosterAssignment)) {
                $classSection = $this->activeClassSection($rosterAssignment['class_section_id'], $learningSet->school_id);
                $this->assertRosterAssignmentAuthority($actor, $classSection);
                $studentProfileIds = $this->activeRosterStudentProfileIds($classSection);
                $learningSet->assignments()->where('status', 'active')->update(['status' => 'inactive']);

                /** @var Collection<int, \App\Models\LearningSetAssignment> $existingAssignments */
                $existingAssignments = $learningSet->assignments()
                    ->whereIn('student_profile_id', $studentProfileIds->all())
                    ->get()
                    ->keyBy('student_profile_id');

                foreach ($studentProfileIds as $studentProfileId) {
                    $assignmentData = [
                        'school_id' => $learningSet->school_id,
                        'assignment_mode' => 'roster',
                        'class_section_id' => $classSection->id,
                        'student_profile_id' => $studentProfileId,
                        'status' => 'active',
                        'assigned_at' => now(),
                    ];

                    $existingAssignment = $existingAssignments->get($studentProfileId);

                    if ($existingAssignment !== null) {
                        $existingAssignment->forceFill($assignmentData)->save();
                    } else {
                        $learningSet->assignments()->create($assignmentData);
                    }
                }
            }

            $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $learningSet->school_id, LearningSet::class, $learningSet->uuid, [
                'action' => 'update',
            ]);

            return $this->load($learningSet->refresh());
        });
    }

    public function status(User $actor, TenantContext $context, string $uuid, string $status): LearningSet
    {
        $learningSet = $this->resolve($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $learningSet);
        $input = $status === 'active' ? LifecycleInput::activate() : LifecycleInput::deactivate();
        $service = $this;
        $updated = $this->lifecycle->transition($learningSet, $input, fn (LearningSet $resource) => $service->assertActivationDependencies($resource));
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, LearningSet::class, $updated->uuid, ['action' => $status]);

        return $this->load($updated);
    }

    public function delete(User $actor, TenantContext $context, string $uuid): LearningSet
    {
        $learningSet = $this->resolve($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $learningSet);
        $learningSet->forceFill(['deleted_by_user_id' => $actor->id])->save();
        $updated = $this->lifecycle->transition($learningSet, LifecycleInput::delete());
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, LearningSet::class, $updated->uuid, ['action' => 'delete']);

        return $this->load($updated);
    }

    public function restore(User $actor, TenantContext $context, string $uuid): LearningSet
    {
        $learningSet = $this->resolve($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $learningSet);
        $updated = $this->lifecycle->transition($learningSet, LifecycleInput::restore());
        $updated->forceFill([
            'restored_at' => now(),
            'restored_by_user_id' => $actor->id,
        ])->save();
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, LearningSet::class, $updated->uuid, ['action' => 'restore']);

        return $this->load($updated->refresh());
    }

    private function resolve(TenantContext $context, string $uuid): LearningSet
    {
        $school = $this->schoolContext->requireResolved($context);
        $learningSet = $this->lookup->findLearningSet($uuid, $school->id);

        if ($learningSet === null) {
            throw (new ModelNotFoundException())->setModel(LearningSet::class, [$uuid]);
        }

        return $learningSet;
    }

    private function load(LearningSet $learningSet): LearningSet
    {
        return $learningSet->load([
            'school',
            'owner',
            'academicPeriod',
            'entries.contentItem',
            'entries.questionnaire',
            'assignments.studentProfile',
            'assignments.classSection',
        ]);
    }

    private function activeClassSection(string $uuid, int $schoolId): ClassSection
    {
        $classSection = ClassSection::query()
            ->where('uuid', $uuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($classSection === null) {
            throw ValidationException::withMessages(['roster_assignment.class_section_id' => ['The class section must be active and belong to the resolved school.']]);
        }

        if (! $classSection->rosterMemberships()->where('status', 'active')->exists()) {
            throw new ConflictException('Roster-aware assignments require at least one active roster membership.');
        }

        return $classSection;
    }

    public function assertActivationDependencies(LearningSet $learningSet): void
    {
        if ($learningSet->entries()->count() === 0 || $learningSet->assignments()->where('status', 'active')->count() === 0) {
            throw new ConflictException('Learning set requires active entries and assignments before activation.');
        }
    }

    private function assertRosterAssignmentAuthority(User $actor, ClassSection $classSection): void
    {
        if ($actor->hasSchoolPermission('users.manage', $classSection->school_id)) {
            return;
        }

        $hasActiveAssignment = TeacherAssignment::query()
            ->where('school_id', $classSection->school_id)
            ->where('class_section_id', $classSection->id)
            ->where('teacher_user_id', $actor->id)
            ->where('status', 'active')
            ->exists();

        if (! $hasActiveAssignment) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Teachers require an active assignment to update roster-aware learning-set assignments.');
        }
    }

    /**
     * @return Collection<int, int>
     */
    private function activeRosterStudentProfileIds(ClassSection $classSection): Collection
    {
        return $classSection->rosterMemberships()
            ->where('status', 'active')
            ->pluck('student_profile_id')
            ->unique()
            ->values();
    }
}
