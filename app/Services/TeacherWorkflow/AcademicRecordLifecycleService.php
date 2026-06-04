<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\User;
use App\Repositories\TeacherWorkflow\TeacherWorkflowLookupRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class AcademicRecordLifecycleService
{
    public function __construct(
        private readonly SchoolContextGuard $schoolContext,
        private readonly TeacherWorkflowLookupRepository $lookup,
        private readonly LifecycleTransitionService $lifecycle,
        private readonly TeacherWorkflowAuditLogger $audit,
    ) {}

    public function getGrade(User $actor, TenantContext $context, string $uuid): GradeRecord
    {
        $record = $this->resolveGrade($context, $uuid);
        Gate::forUser($actor)->authorize('view', $record);

        return $this->load($record);
    }

    public function getAttendance(User $actor, TenantContext $context, string $uuid): AttendanceRecord
    {
        $record = $this->resolveAttendance($context, $uuid);
        Gate::forUser($actor)->authorize('view', $record);

        return $this->load($record);
    }

    public function status(User $actor, TenantContext $context, string $type, string $uuid, string $status): GradeRecord|AttendanceRecord
    {
        $record = $type === 'grade' ? $this->resolveGrade($context, $uuid) : $this->resolveAttendance($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $record);
        $input = $status === 'active' ? LifecycleInput::activate() : LifecycleInput::deactivate();
        $service = $this;
        $updated = $this->lifecycle->transition($record, $input, fn (Model $resource) => $service->assertActivationDependencies($resource));
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, $updated::class, $updated->uuid, ['action' => $status]);

        return $this->load($updated);
    }

    public function delete(User $actor, TenantContext $context, string $type, string $uuid): GradeRecord|AttendanceRecord
    {
        $record = $type === 'grade' ? $this->resolveGrade($context, $uuid) : $this->resolveAttendance($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $record);
        $record->forceFill(['deleted_by_user_id' => $actor->id])->save();
        $updated = $this->lifecycle->transition($record, LifecycleInput::delete());
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, $updated::class, $updated->uuid, ['action' => 'delete']);

        return $this->load($updated);
    }

    public function restore(User $actor, TenantContext $context, string $type, string $uuid): GradeRecord|AttendanceRecord
    {
        $record = $type === 'grade' ? $this->resolveGrade($context, $uuid) : $this->resolveAttendance($context, $uuid);
        Gate::forUser($actor)->authorize('lifecycle', $record);
        $updated = $this->lifecycle->transition($record, LifecycleInput::restore());
        $updated->forceFill([
            'restored_at' => now(),
            'restored_by_user_id' => $actor->id,
        ])->save();
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, $updated::class, $updated->uuid, ['action' => 'restore']);

        return $this->load($updated->refresh());
    }

    public function assertActivationDependencies(Model $record): void
    {
        if ($record->studentProfile?->status !== 'active' || $record->academicPeriod?->status !== 'active') {
            throw new ConflictException('Academic record requires active student and academic period before activation.');
        }
    }

    public function resolveGrade(TenantContext $context, string $uuid): GradeRecord
    {
        $school = $this->schoolContext->requireResolved($context);
        $record = $this->lookup->findGrade($uuid, $school->id);

        if ($record === null) {
            throw (new ModelNotFoundException())->setModel(GradeRecord::class, [$uuid]);
        }

        return $record;
    }

    public function resolveAttendance(TenantContext $context, string $uuid): AttendanceRecord
    {
        $school = $this->schoolContext->requireResolved($context);
        $record = $this->lookup->findAttendance($uuid, $school->id);

        if ($record === null) {
            throw (new ModelNotFoundException())->setModel(AttendanceRecord::class, [$uuid]);
        }

        return $record;
    }

    public function load(GradeRecord|AttendanceRecord $record): GradeRecord|AttendanceRecord
    {
        return $record->load(['school', 'studentProfile', 'academicPeriod', 'recorder', 'originalRecorder', 'corrections.school', 'corrections.actor']);
    }
}
