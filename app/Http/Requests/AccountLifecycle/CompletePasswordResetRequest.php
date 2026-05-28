<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountLifecycle;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Validator;

final class CompletePasswordResetRequest extends ApiFormRequest
{
    private const COMMON_PASSWORDS = [
        'password',
        'password123',
        'commonpassword',
        'qwerty123456',
        'letmein123456',
    ];

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:32', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:128'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if (in_array(strtolower((string) $this->input('password')), self::COMMON_PASSWORDS, true)) {
                $validator->errors()->add('password', 'The password is too common.');
            }
        });
    }
}
