<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\School;
use Illuminate\Validation\Rule;

final class SchoolUpdateRequest extends SchoolCreateRequest
{
    public function rules(): array
    {
        /** @var School|null $school */
        $school = School::query()->where('uuid', $this->route('schoolId'))->first();

        $rules = parent::rules();
        $rules['document'] = [
            'sometimes',
            'string',
            function (string $attribute, mixed $value, \Closure $fail) use ($school): void {
                $submitted = preg_replace('/\D+/', '', (string) $value);
                if ($school !== null && $submitted !== $school->document) {
                    $fail('The school document cannot be changed.');
                }
            },
        ];
        $rules['inep_code'] = ['sometimes', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('schools', 'inep_code')->ignore($school?->id)];
        $rules['status'] = ['sometimes', 'integer', Rule::in([1, 0])];
        $rules['name'] = ['sometimes', 'string', 'max:255'];
        $rules['email'] = ['sometimes', 'email', 'max:100', Rule::unique('schools', 'normalized_email')->ignore($school?->id)];

        return $rules;
    }
}
