<?php

declare(strict_types=1);

namespace App\Http\Resources\TeacherWorkflow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseTeacherWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    protected function lifecycleMeta(): array
    {
        return [
            'status' => $this->resource->status ?? null,
            'deleted_at' => $this->resource->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function envelope(array $data): array
    {
        return [
            'data' => $data,
            'meta' => (object) [],
        ];
    }

    abstract public function toArray(Request $request): array;
}
