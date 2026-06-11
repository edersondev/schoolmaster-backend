<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Platform;

use App\Http\Requests\ApiFormRequest;
use App\Models\School;
use App\Services\PlatformSupport\PlatformSupportAuditService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class PlatformSupportFormRequest extends ApiFormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        $actor = $this->attributes->get('auth_user');
        $school = $this->resolveTargetSchool();
        $correlationId = (string) ($this->input('correlation_id') ?: $this->query('correlation_id') ?: 'validation-rejected');
        $reasonCode = (string) ($this->input('reason_code') ?: $this->query('reason_code') ?: 'validation_rejected');

        app(PlatformSupportAuditService::class)->record(
            actor: $actor,
            action: 'validation_rejected',
            outcome: 'rejected',
            reasonCode: $reasonCode,
            correlationId: $correlationId,
            school: $school,
            metadata: [
                'route' => (string) $this->route()?->getName(),
                'field_count' => count($validator->errors()->toArray()),
            ],
        );

        throw new ValidationException($validator);
    }

    private function resolveTargetSchool(): ?School
    {
        $schoolUuid = $this->route('schoolId') ?: $this->input('school_id') ?: $this->query('school_id');

        if (! is_string($schoolUuid) || $schoolUuid === '') {
            return null;
        }

        return School::query()->where('uuid', $schoolUuid)->first();
    }
}
