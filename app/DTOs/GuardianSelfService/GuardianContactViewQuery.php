<?php

declare(strict_types=1);

namespace App\DTOs\GuardianSelfService;

final readonly class GuardianContactViewQuery
{
    public function __construct(public GuardianStudentTarget $target) {}
}
