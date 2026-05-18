<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TeacherContentItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessTeacherContentScan implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $teacherContentItemId) {}

    public function handle(): void
    {
        $content = TeacherContentItem::query()->find($this->teacherContentItemId);

        if ($content === null || $content->scan_status !== 'pending') {
            return;
        }

        // External scanner integration must provide a trusted result before content is marked clean.
    }
}
