<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherWorkflow;

use App\Http\Requests\ApiFormRequest;
use App\Http\Requests\TeacherWorkflow\Concerns\ValidatesTeacherWorkflowRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AcademicRecordRequest extends ApiFormRequest
{
    use ValidatesTeacherWorkflowRequests;

    public function rules(): array
    {
        if (str_ends_with($this->path(), '/status')) {
            return [
                'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ];
        }

        if (str_ends_with($this->path(), '/correction')) {
            if ($this->is('api/v1/grades/*')) {
                return [
                    'grade_value' => ['required', 'numeric', 'min:0', 'max:100'],
                    'grade_label' => ['sometimes', 'nullable', 'string', 'max:50'],
                    'correction_reason' => ['required', 'string', 'min:10', 'max:500'],
                ];
            }

            return [
                'attendance_status' => ['required', 'string', Rule::in(['present', 'absent', 'late', 'excused', 'remote', 'suspended'])],
                'correction_reason' => ['required', 'string', 'min:10', 'max:500'],
            ];
        }

        return [];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $this->validateTeacherWorkflowRequest($validator, ['active', 'inactive']);
    }
}
