<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\TeacherContentFolder;
use App\Models\TeacherContentItem;
use App\Models\User;
use App\Repositories\TeacherWorkflow\TeacherWorkflowLookupRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class TeacherContentService
{
    public function __construct(
        private readonly SchoolContextGuard $schoolContext,
        private readonly TeacherWorkflowLookupRepository $lookup,
        private readonly HistoricalMeaningGuard $historicalMeaning,
        private readonly LifecycleTransitionService $lifecycle,
        private readonly TeacherWorkflowAuditLogger $audit,
    ) {}

    public function get(User $actor, TenantContext $context, string $contentUuid): TeacherContentItem
    {
        $content = $this->resolve($context, $contentUuid);
        Gate::forUser($actor)->authorize('view', $content);

        return $content->load(['school', 'owner', 'folder']);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function update(User $actor, TenantContext $context, string $contentUuid, array $changes): TeacherContentItem
    {
        $content = $this->resolve($context, $contentUuid);
        Gate::forUser($actor)->authorize('update', $content);
        $this->historicalMeaning->assertContentEditable($content, $changes);

        if (array_key_exists('folder_id', $changes)) {
            $changes['folder_id'] = $this->folderId($changes['folder_id'], $content->school_id);
        }

        return DB::transaction(function () use ($actor, $content, $changes): TeacherContentItem {
            $content->forceFill($changes)->save();
            $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $content->school_id, TeacherContentItem::class, $content->uuid, [
                'action' => 'update',
                'changed_fields' => array_keys($changes),
            ]);

            return $content->refresh()->load(['school', 'owner', 'folder']);
        });
    }

    public function status(User $actor, TenantContext $context, string $contentUuid, string $status): TeacherContentItem
    {
        $content = $this->resolve($context, $contentUuid);
        Gate::forUser($actor)->authorize('lifecycle', $content);
        $input = $status === 'active' ? LifecycleInput::activate() : LifecycleInput::deactivate();

        try {
            $updated = $this->lifecycle->transition($content, $input, function (TeacherContentItem $resource): void {
                if ($resource->scan_status !== 'clean') {
                    throw new ConflictException('Only clean scanned content can be activated.');
                }
            });
        } catch (ConflictException $exception) {
            $this->audit->record('teacher_workflow.conflict', 'conflict', $actor->id, $content->school_id, TeacherContentItem::class, $content->uuid, [
                'action' => $status,
                'conflict_category' => 'lifecycle_dependency',
            ]);

            throw $exception;
        }

        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, TeacherContentItem::class, $updated->uuid, [
            'action' => $status,
        ]);

        return $updated->load(['school', 'owner', 'folder']);
    }

    public function delete(User $actor, TenantContext $context, string $contentUuid): TeacherContentItem
    {
        $content = $this->resolve($context, $contentUuid);
        Gate::forUser($actor)->authorize('lifecycle', $content);
        $content->forceFill(['deleted_by_user_id' => $actor->id])->save();
        $updated = $this->lifecycle->transition($content, LifecycleInput::delete());
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, TeacherContentItem::class, $updated->uuid, [
            'action' => 'delete',
        ]);

        return $updated->load(['school', 'owner', 'folder']);
    }

    public function restore(User $actor, TenantContext $context, string $contentUuid): TeacherContentItem
    {
        $content = $this->resolve($context, $contentUuid);
        Gate::forUser($actor)->authorize('lifecycle', $content);
        $updated = $this->lifecycle->transition($content, LifecycleInput::restore());
        $updated->forceFill([
            'restored_at' => now(),
            'restored_by_user_id' => $actor->id,
        ])->save();
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, TeacherContentItem::class, $updated->uuid, [
            'action' => 'restore',
        ]);

        return $updated->refresh()->load(['school', 'owner', 'folder']);
    }

    /**
     * @return array{content: TeacherContentItem, download_url: string, expires_at: \DateTimeInterface}
     */
    public function download(User $actor, TenantContext $context, string $contentUuid): array
    {
        $content = $this->resolve($context, $contentUuid);

        try {
            Gate::forUser($actor)->authorize('download', $content);
        } catch (AuthorizationException $exception) {
            $this->audit->record('teacher_workflow.download', 'denied', $actor->id, $content->school_id, TeacherContentItem::class, $content->uuid, [
                'denial_category' => $this->downloadDenialCategory($content),
            ]);

            throw $exception;
        }

        $expiresAt = now()->addMinutes(10);
        $this->audit->record('teacher_workflow.download', 'success', $actor->id, $content->school_id, TeacherContentItem::class, $content->uuid, [
            'scan_status' => $content->scan_status,
            'lifecycle_status' => $content->status,
        ]);

        return [
            'content' => $content->load(['school', 'owner', 'folder']),
            'download_url' => url('/api/v1/teacher-content/'.$content->uuid.'/download?expires='.$expiresAt->timestamp),
            'expires_at' => $expiresAt,
        ];
    }

    private function resolve(TenantContext $context, string $uuid): TeacherContentItem
    {
        $school = $this->schoolContext->requireResolved($context);
        $content = $this->lookup->findTeacherContent($uuid, $school->id);

        if ($content === null) {
            throw (new ModelNotFoundException())->setModel(TeacherContentItem::class, [$uuid]);
        }

        return $content;
    }

    private function folderId(?string $folderUuid, int $schoolId): ?int
    {
        if ($folderUuid === null) {
            return null;
        }

        $folder = TeacherContentFolder::query()
            ->where('uuid', $folderUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($folder === null) {
            throw ValidationException::withMessages([
                'folder_id' => ['The selected folder is invalid for the resolved school.'],
            ]);
        }

        return $folder->id;
    }

    private function downloadDenialCategory(TeacherContentItem $content): string
    {
        if ($content->status !== 'active' || $content->trashed()) {
            return 'inactive_or_deleted';
        }

        if ($content->scan_status !== 'clean') {
            return 'scan_not_clean';
        }

        return 'authorization';
    }
}
