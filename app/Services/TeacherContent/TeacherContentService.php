<?php

declare(strict_types=1);

namespace App\Services\TeacherContent;

use App\DTOs\TeacherContent\CreateTeacherContentData;
use App\DTOs\TenantContext;
use App\Jobs\ProcessTeacherContentScan;
use App\Models\TeacherContentFolder;
use App\Models\TeacherContentItem;
use App\Models\User;
use App\Services\Concerns\AuthorizesTeacherWorkflows;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException as FlysystemException;
use Throwable;

final class TeacherContentService
{
    use AuthorizesTeacherWorkflows;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly TeacherWorkflowListQuery $listQuery,
        private readonly TeacherContentUploadValidator $uploadValidator,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'teacher_content.view');

        return TeacherContentItem::query()
            ->with(['school', 'owner', 'folder'])
            ->where('school_id', $school->id)
            ->orderBy('title')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateTeacherContentData $data): TeacherContentItem
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'teacher_content.manage');
        $folder = $this->resolveFolder($data->folderId, $school->id);
        $fileData = $this->uploadValidator->validate($data->file, $data->contentType);
        $uuid = (string) Str::uuid();
        $path = $this->uploadValidator->storagePath($school->uuid, $uuid, $fileData['safe_filename']);

        try {
            $stored = Storage::disk('teacher_content')->putFileAs(
                dirname($path),
                $data->file,
                basename($path),
                ['visibility' => 'private'],
            );
        } catch (FlysystemException) {
            throw ValidationException::withMessages([
                'file' => ['The file could not be stored in private teacher content storage.'],
            ]);
        }

        if ($stored === false) {
            throw ValidationException::withMessages([
                'file' => ['The file could not be stored in private teacher content storage.'],
            ]);
        }

        try {
            $content = DB::transaction(function () use ($actor, $data, $fileData, $folder, $path, $school, $uuid): TeacherContentItem {
                return TeacherContentItem::query()->create([
                    'uuid' => $uuid,
                    'school_id' => $school->id,
                    'owner_user_id' => $actor->id,
                    'folder_id' => $folder?->id,
                    'title' => $data->title,
                    'description' => $data->description,
                    'content_type' => $data->contentType,
                    'declared_content_type' => $fileData['declared_content_type'],
                    'detected_content_type' => $fileData['detected_content_type'],
                    'file_size_bytes' => $fileData['file_size_bytes'],
                    'storage_path' => $path,
                    'scan_status' => 'pending',
                    'status' => 'active',
                ])->load(['school', 'owner', 'folder']);
            });
        } catch (Throwable $exception) {
            Storage::disk('teacher_content')->delete($path);

            throw $exception;
        }

        ProcessTeacherContentScan::dispatch($content->id);

        return $content;
    }

    private function resolveFolder(?string $folderUuid, int $schoolId): ?TeacherContentFolder
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

        return $folder;
    }
}
