<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentProfileLifecycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student_profile' => (new StudentProfileResource($this->resource['student_profile']))->resolve(),
            'enrollment_history' => (new EnrollmentHistoryResource($this->resource['enrollment_history']))->resolve(),
        ];
    }
}
