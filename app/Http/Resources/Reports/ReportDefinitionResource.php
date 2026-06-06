<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $state = $this->lifecycle_state;

        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
            'fields' => $this->fields,
            'filters' => $this->filters,
            'grouping' => $this->grouping,
            'sorting' => $this->sorting,
            'output_formats' => $this->output_formats,
            'lifecycle_state' => $state instanceof BackedEnum ? $state->value : $state,
            'version' => $this->version,
            'created_by_user_id' => $this->creator?->uuid,
            'updated_by_user_id' => $this->updater?->uuid,
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
