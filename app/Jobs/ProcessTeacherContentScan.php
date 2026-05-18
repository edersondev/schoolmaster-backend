<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TeacherContentItem;
use App\Services\TeacherContent\TeacherContentScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessTeacherContentScan implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $teacherContentItemId) {}

    public function handle(TeacherContentScanService $scanService): void
    {
        $content = TeacherContentItem::query()->find($this->teacherContentItemId);

        if ($content === null || $content->scan_status !== 'pending') {
            return;
        }

        $scanService->markClean($content);
    }
}
