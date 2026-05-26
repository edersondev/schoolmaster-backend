<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AcademicYearLifecycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
        ];
    }
}
