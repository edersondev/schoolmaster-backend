<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;
use App\Services\AdministrationLifecycle\LifecycleAction;

final class BulkLifecycleActionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', 'in:users,roles,academic_years,academic_periods,guardians'],
            'action' => ['required', 'string', 'in:'.implode(',', LifecycleAction::values())],
            'record_ids' => ['required', 'array', 'min:1', 'max:'.LifecycleAction::MAX_BULK_RECORDS],
            'record_ids.*' => ['string', 'uuid', 'distinct'],
            'effective_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:1', 'max:500'],
        ];
    }
}
