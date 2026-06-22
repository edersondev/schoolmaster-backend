<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentResponseAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AssessmentQueryRepository
{
    public function findAttempt(string $uuid, int $schoolId): ?AssessmentResponseAttempt
    {
        return AssessmentResponseAttempt::query()
            ->with(['answers.fileAttachment', 'gradingOutcomes', 'questionnaire', 'learningSet', 'studentProfile'])
            ->where('uuid', $uuid)
            ->where('school_id', $schoolId)
            ->first();
    }

    public function paginateAttempts(int $schoolId, array $filters = []): LengthAwarePaginator
    {
        return AssessmentResponseAttempt::query()
            ->with(['questionnaire', 'learningSet', 'studentProfile'])
            ->where('school_id', $schoolId)
            ->when($filters['questionnaire_id'] ?? null, fn ($query, string $uuid) => $query->whereHas('questionnaire', fn ($nested) => $nested->where('uuid', $uuid)))
            ->when($filters['learning_set_id'] ?? null, fn ($query, string $uuid) => $query->whereHas('learningSet', fn ($nested) => $nested->where('uuid', $uuid)))
            ->when($filters['grading_status'] ?? null, fn ($query, string $status) => $query->where('grading_status', $status))
            ->latest('submitted_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findCleanFile(string $attemptUuid, string $fileUuid, int $schoolId): ?AssessmentFileAttachment
    {
        return AssessmentFileAttachment::query()
            ->with('answer.responseAttempt')
            ->where('uuid', $fileUuid)
            ->where('school_id', $schoolId)
            ->where('scan_status', 'clean')
            ->whereHas('answer.responseAttempt', fn ($query) => $query->where('uuid', $attemptUuid)->where('school_id', $schoolId))
            ->first();
    }
}
