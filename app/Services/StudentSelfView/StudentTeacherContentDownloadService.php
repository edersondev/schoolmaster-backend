<?php

declare(strict_types=1);

namespace App\Services\StudentSelfView;

use App\DTOs\TenantContext;
use App\Models\TeacherContentItem;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentSelfView;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class StudentTeacherContentDownloadService
{
    use AuthorizesStudentSelfView;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function resolveDownload(User $actor, TenantContext $context, string $contentUuid): TeacherContentItem
    {
        $school = $this->tenantContext->requireSchool($context);
        $student = $this->activeStudentProfileFor($actor, $school);

        $content = TeacherContentItem::query()
            ->where('uuid', $contentUuid)
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->where('scan_status', 'clean')
            ->whereHas('learningSetEntries', fn ($entries) => $entries
                ->where('entry_type', 'content_item')
                ->whereHas('learningSet', fn ($learningSets) => $learningSets
                    ->where('school_id', $school->id)
                    ->whereIn('status', ['published', 'active'])
                    ->whereHas('assignments', fn ($assignments) => $assignments
                        ->where('student_profile_id', $student->id)
                        ->where('status', 'active'))))
            ->first();

        if ($content === null) {
            throw (new ModelNotFoundException)->setModel(TeacherContentItem::class);
        }

        return $content;
    }
}
