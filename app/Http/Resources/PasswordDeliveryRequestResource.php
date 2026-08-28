<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PasswordDeliveryRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => 'requested',
            'delivery_channel' => 'email',
            'delivery_requested_at' => $this->delivery_requested_at?->toIso8601String(),
        ];
    }
}
