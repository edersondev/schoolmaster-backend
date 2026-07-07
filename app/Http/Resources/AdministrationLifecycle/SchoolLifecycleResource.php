<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use App\Http\Resources\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SchoolLifecycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'inep_code' => $this->inep_code,
            'name' => $this->name,
            'document' => $this->document,
            'status' => $this->status === 'active' ? 1 : (int) $this->status,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address ? (new AddressResource($this->address))->resolve() : null,
        ];
    }
}
