<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;

final class UpdateAcademicYearLifecycleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', 'in:planned,active,closed,inactive'],
        ];
    }
}
