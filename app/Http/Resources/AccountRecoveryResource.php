<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AccountLock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountRecoveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof AccountLock) {
            return [
                'id' => $this->uuid,
                'user_id' => $this->user?->uuid,
                'school_id' => $this->school?->uuid,
                'lock_type' => $this->lock_type,
                'status' => $this->status,
                'reason' => $this->reason,
                'locked_at' => $this->locked_at?->toIso8601String(),
                'cleared_at' => $this->cleared_at?->toIso8601String(),
            ];
        }

        return $this->resource;
    }
}
