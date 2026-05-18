<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'scope' => $this->scope,
            'name' => $this->name,
            'status' => $this->status,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
