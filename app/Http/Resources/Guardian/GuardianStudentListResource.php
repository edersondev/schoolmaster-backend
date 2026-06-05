<?php

declare(strict_types=1);

namespace App\Http\Resources\Guardian;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GuardianStudentListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'school_id' => $this->resource['school_id'],
            'registration_number' => $this->resource['registration_number'],
            'full_name' => $this->resource['full_name'],
            'status' => $this->resource['status'],
            'enrolled_at' => $this->resource['enrolled_at'],
            'relationship_label' => $this->resource['relationship_label'],
            'current_academic_year_id' => $this->resource['current_academic_year_id'],
        ];
    }
}
