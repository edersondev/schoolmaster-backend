<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentSelfView;

use App\Http\Requests\ApiFormRequest;

final class DownloadStudentTeacherContentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [];
    }
}
