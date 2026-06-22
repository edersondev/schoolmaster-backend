<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\AssessmentFileAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AssessmentFileDownloadService
{
    public function __construct(
        private readonly AssessmentTenantScopeService $tenantScope,
        private readonly AssessmentReviewAuthorizationService $authorization,
        private readonly AssessmentAuditService $audit,
    ) {}

    public function download(User $actor, TenantContext $tenantContext, string $attemptUuid, string $fileUuid): StreamedResponse
    {
        $context = $this->tenantScope->actorContext($actor, $tenantContext);
        $file = AssessmentFileAttachment::query()
            ->with('answer.responseAttempt.questionnaire')
            ->where('uuid', $fileUuid)
            ->where('school_id', $context->school->id)
            ->whereHas('answer.responseAttempt', fn ($query) => $query->where('uuid', $attemptUuid)->where('school_id', $context->school->id))
            ->first();

        if ($file === null || $file->answer?->responseAttempt === null) {
            throw (new ModelNotFoundException)->setModel(AssessmentFileAttachment::class, [$fileUuid]);
        }

        $attempt = $file->answer->responseAttempt;
        $this->authorization->assertCanReview($actor, $attempt);

        if ($file->scan_status === 'pending') {
            $this->audit->record($context, 'download', 'denied', 'file_scan_pending', $file, ['scan_status' => 'pending']);
            throw new HttpException(423, 'Answer file is pending malware scan and unavailable.');
        }

        if ($file->scan_status === 'failed') {
            $this->audit->record($context, 'download', 'denied', 'file_scan_failed', $file, ['scan_status' => 'failed']);
            throw new HttpException(424, 'Answer file failed malware scan and remains unavailable.');
        }

        if ($file->scan_status !== 'clean' || $file->availability_state !== 'clean_download_allowed') {
            $this->audit->record($context, 'download', 'denied', 'file_unavailable', $file);
            throw new ConflictException('Answer file is unavailable for download.');
        }

        if (! Storage::disk('local')->exists($file->storage_path)) {
            throw (new ModelNotFoundException)->setModel(AssessmentFileAttachment::class, [$fileUuid]);
        }

        $this->audit->record($context, 'download', 'succeeded', 'file_downloaded', $file, [
            'file_category' => $file->file_category,
            'file_size_bytes' => $file->file_size_bytes,
        ]);

        return Storage::disk('local')->download($file->storage_path, $file->sanitized_filename);
    }
}
