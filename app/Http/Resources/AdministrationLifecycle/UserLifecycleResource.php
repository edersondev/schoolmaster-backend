<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserLifecycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'full_name' => $this->full_name ?? $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
