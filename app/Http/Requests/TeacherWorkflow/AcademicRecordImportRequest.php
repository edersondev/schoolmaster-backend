<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherWorkflow;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AcademicRecordImportRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rowRules = [
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.student_profile_id' => ['required', 'uuid'],
            'rows.*.academic_period_id' => ['required', 'uuid'],
        ];

        if ($this->is('api/v1/grades/imports')) {
            return $rowRules + [
                'rows.*.grade_value' => ['required', 'numeric', 'min:0', 'max:100'],
                'rows.*.grade_label' => ['sometimes', 'nullable', 'string', 'max:50'],
            ];
        }

        return $rowRules + [
            'rows.*.attendance_date' => ['required', 'date'],
            'rows.*.attendance_status' => ['required', 'string', Rule::in(['present', 'absent', 'late', 'excused', 'remote', 'suspended'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if (! $this->isJson()) {
                $validator->errors()->add('rows', 'Imports accept JSON payloads only.');
            }

            $rows = $this->input('rows');

            if (! is_array($rows)) {
                return;
            }

            $allowed = $this->is('api/v1/grades/imports')
                ? ['student_profile_id', 'academic_period_id', 'grade_value', 'grade_label']
                : ['student_profile_id', 'academic_period_id', 'attendance_date', 'attendance_status'];

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    $validator->errors()->add("rows.$index", 'Each import row must be an object.');

                    continue;
                }

                foreach (array_keys($row) as $field) {
                    if (! in_array($field, $allowed, true)) {
                        $validator->errors()->add("rows.$index.$field", 'This field is not documented for this import row.');
                    }
                }
            }
        });
    }
}
