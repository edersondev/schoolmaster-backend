<?php

declare(strict_types=1);

namespace App\Http\Resources\TeacherWorkflow;

use App\Models\ImportRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AcademicRecordImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ImportRun $run */
        $run = $this->resource;

        return [
            'id' => $run->uuid,
            'school_id' => $run->school?->uuid,
            'actor_user_id' => $run->actor?->uuid,
            'import_type' => $run->import_type,
            'row_count' => $run->row_count,
            'accepted_row_count' => $run->accepted_row_count,
            'rejected_row_count' => $run->rejected_row_count,
            'status' => $run->status,
            'error_summary' => $run->error_summary ?? [],
        ];
    }
}
