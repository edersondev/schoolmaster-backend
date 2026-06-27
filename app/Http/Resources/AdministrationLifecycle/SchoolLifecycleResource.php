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
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address ? (new AddressResource($this->address))->resolve() : null,
        ];
    }
}
