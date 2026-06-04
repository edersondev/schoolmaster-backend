<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherWorkflow\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesTeacherWorkflowRequests
{
    /**
     * @param  array<int, string>  $allowedStatuses
     * @param  array<int, string>  $allowedSorts
     */
    protected function validateTeacherWorkflowRequest(
        Validator $validator,
        array $allowedStatuses = ['active', 'inactive', 'deleted'],
        array $allowedSorts = [],
    ): void {
        $validator->after(function (Validator $validator) use ($allowedStatuses, $allowedSorts): void {
            $status = $this->input('status');

            if (is_string($status) && ! in_array($status, $allowedStatuses, true)) {
                $validator->errors()->add('status', 'The requested lifecycle status is not supported.');
            }

            $sort = $this->input('sort');

            if (is_string($sort) && $sort !== '') {
                $normalized = ltrim($sort, '-');

                if ($allowedSorts !== [] && ! in_array($normalized, $allowedSorts, true)) {
                    $validator->errors()->add('sort', 'The requested sort is not supported.');
                }
            }

            foreach (['include', 'filter'] as $unsupportedField) {
                if ($this->has($unsupportedField)) {
                    $validator->errors()->add($unsupportedField, 'This field is not documented for this request.');
                }
            }
        });
    }
}
