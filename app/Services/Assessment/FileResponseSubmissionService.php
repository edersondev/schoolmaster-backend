<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentFileAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class FileResponseSubmissionService
{
    public function __construct(private readonly AssessmentFileRuleService $rules) {}

    public function persist(AssessmentAnswer $answer, UploadedFile $file): AssessmentFileAttachment
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'answers' => ['Uploaded assessment file is invalid.'],
            ]);
        }

        try {
            $detectedCategory = $this->rules->assertAllowed($file);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'answers' => [$exception->getMessage()],
            ]);
        }

        $declaredCategory = $this->rules->categoryForMime($file->getClientMimeType());

        if ($declaredCategory === null || $declaredCategory !== $detectedCategory) {
            throw ValidationException::withMessages([
                'answers' => ['Uploaded assessment file declared type does not match detected type.'],
            ]);
        }

        $sanitized = $this->rules->sanitizeFilename($file->getClientOriginalName());
        $storageName = Str::uuid()->toString().'_'.$sanitized;
        $path = 'assessment-responses/'.$answer->school->uuid.'/'.$answer->uuid.'/'.$storageName;

        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        return AssessmentFileAttachment::query()->create([
            'school_id' => $answer->school_id,
            'assessment_answer_id' => $answer->id,
            'original_filename' => $file->getClientOriginalName(),
            'sanitized_filename' => $sanitized,
            'declared_content_type' => $file->getClientMimeType(),
            'detected_content_type' => $file->getMimeType(),
            'file_category' => $detectedCategory,
            'file_size_bytes' => (int) $file->getSize(),
            'storage_path' => $path,
            'scan_status' => 'pending',
            'availability_state' => 'scan_pending',
            'uploaded_at' => now(),
        ]);
    }
}
