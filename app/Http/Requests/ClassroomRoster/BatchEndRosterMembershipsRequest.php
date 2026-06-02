<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class BatchEndRosterMembershipsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'effective_end_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:1', 'max:500'],
            'roster_membership_ids' => ['required', 'array', 'min:1', 'max:100'],
            'roster_membership_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
