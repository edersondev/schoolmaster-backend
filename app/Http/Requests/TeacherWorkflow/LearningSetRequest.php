<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherWorkflow;

use App\Http\Requests\ApiFormRequest;
use App\Http\Requests\TeacherWorkflow\Concerns\ValidatesTeacherWorkflowRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class LearningSetRequest extends ApiFormRequest
{
    use ValidatesTeacherWorkflowRequests;

    public function rules(): array
    {
        if (str_ends_with($this->path(), '/status')) {
            return [
                'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ];
        }

        if ($this->isMethod('PATCH')) {
            return [
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
                'due_at' => ['sometimes', 'nullable', 'date'],
                'entries' => ['sometimes', 'array', 'min:1'],
                'entries.*.entry_type' => ['required_with:entries', 'string', Rule::in(['content_item', 'questionnaire'])],
                'entries.*.entry_reference_id' => ['required_with:entries', 'uuid'],
                'entries.*.sequence' => ['required_with:entries', 'integer', 'min:1'],
                'roster_assignment' => ['sometimes', 'array'],
                'roster_assignment.class_section_id' => ['required_with:roster_assignment', 'uuid'],
            ];
        }

        return [];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $this->validateTeacherWorkflowRequest($validator, ['active', 'inactive']);

        $validator->after(function (Validator $validator): void {
            if (! $this->isMethod('PATCH') || str_ends_with($this->path(), '/status')) {
                return;
            }

            if ($this->all() === []) {
                $validator->errors()->add('payload', 'At least one documented field must be provided.');
            }

            if ($this->has('student_profile_ids')) {
                $validator->errors()->add('student_profile_ids', 'New direct selected-student assignments are not supported for this operation.');
            }

            $sequences = [];
            foreach ((array) $this->input('entries', []) as $index => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $extra = array_diff(array_keys($entry), ['entry_type', 'entry_reference_id', 'sequence']);
                if ($extra !== []) {
                    $validator->errors()->add("entries.$index", 'Entry contains undocumented fields.');
                }

                if (isset($entry['sequence']) && in_array($entry['sequence'], $sequences, true)) {
                    $validator->errors()->add('entries', 'Entry sequences must be unique.');
                }

                if (isset($entry['sequence'])) {
                    $sequences[] = $entry['sequence'];
                }
            }
        });
    }
}
