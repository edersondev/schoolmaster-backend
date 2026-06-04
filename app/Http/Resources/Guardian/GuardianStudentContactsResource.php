<?php

declare(strict_types=1);

namespace App\Http\Resources\Guardian;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GuardianStudentContactsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
