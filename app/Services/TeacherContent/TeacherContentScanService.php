<?php

declare(strict_types=1);

namespace App\Services\TeacherContent;

use App\Models\TeacherContentItem;
use Illuminate\Validation\ValidationException;

final class TeacherContentScanService
{
    public function markClean(TeacherContentItem $content): TeacherContentItem
    {
        return $this->transition($content, 'clean');
    }

    public function markFailed(TeacherContentItem $content): TeacherContentItem
    {
        return $this->transition($content, 'failed');
    }

    public function assertAvailable(TeacherContentItem $content): void
    {
        if ($content->scan_status !== 'clean' || $content->status !== 'active') {
            throw ValidationException::withMessages([
                'entries' => ['Content items must be active and malware scan clean before use.'],
            ]);
        }
    }

    private function transition(TeacherContentItem $content, string $scanStatus): TeacherContentItem
    {
        if ($content->scan_status !== 'pending') {
            throw ValidationException::withMessages([
                'scan_status' => ['Only pending content can transition scan status.'],
            ]);
        }

        $content->forceFill(['scan_status' => $scanStatus])->save();

        return $content->refresh();
    }
}
