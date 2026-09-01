<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateStudentProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid'],
            'registration_number' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'current_academic_year_id' => ['nullable', 'uuid'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'enrolled_at' => ['required', 'date'],
            'guardian_associations' => ['nullable', 'array', 'max:2'],
            'guardian_associations.*' => ['array:guardian_id,relationship_type,full_name,contact_email,contact_phone'],
            'guardian_associations.*.guardian_id' => ['nullable', 'uuid', 'distinct'],
            'guardian_associations.*.relationship_type' => ['required', 'string', 'max:80'],
            'guardian_associations.*.full_name' => ['nullable', 'string', 'max:160'],
            'guardian_associations.*.contact_email' => ['nullable', 'email', 'max:255'],
            'guardian_associations.*.contact_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            $associations = $this->input('guardian_associations', []);

            if (! is_array($associations)) {
                return;
            }

            foreach ($associations as $index => $association) {
                if (! is_array($association)) {
                    continue;
                }

                $hasExistingGuardian = $this->filledValue($association['guardian_id'] ?? null);
                $hasNewGuardianData = $this->filledValue($association['full_name'] ?? null)
                    || $this->filledValue($association['contact_email'] ?? null)
                    || $this->filledValue($association['contact_phone'] ?? null);

                if ($hasExistingGuardian && $hasNewGuardianData) {
                    $validator->errors()->add(
                        "guardian_associations.$index.guardian_id",
                        'Choose an existing guardian or enter new guardian details, not both.',
                    );
                }

                if (! $hasExistingGuardian && ! $this->filledValue($association['full_name'] ?? null)) {
                    $validator->errors()->add(
                        "guardian_associations.$index.full_name",
                        'Full name is required for a new guardian.',
                    );
                }
            }
        });
    }

    private function filledValue(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : $value !== null;
    }
}
