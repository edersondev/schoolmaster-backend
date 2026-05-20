<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentSelfView\DownloadStudentTeacherContentRequest;
use App\Models\TeacherContentItem;
use App\Services\StudentSelfView\StudentTeacherContentDownloadService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudentTeacherContentController extends Controller
{
    public function __construct(private readonly StudentTeacherContentDownloadService $downloads) {}

    public function download(DownloadStudentTeacherContentRequest $request, string $contentItemId): StreamedResponse
    {
        $content = $this->downloads->resolveDownload(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $contentItemId,
        );

        if (! Storage::disk('teacher_content')->exists($content->storage_path)) {
            throw (new ModelNotFoundException)->setModel(TeacherContentItem::class);
        }

        return Storage::disk('teacher_content')->download($content->storage_path, $content->title);
    }
}
