<?php

declare(strict_types=1);

namespace App\DTOs\TeacherWorkflow;

final readonly class LifecycleInput
{
    public function __construct(
        public string $action,
        public ?string $status = null,
    ) {}

    public static function activate(): self
    {
        return new self(action: 'activate', status: 'active');
    }

    public static function deactivate(): self
    {
        return new self(action: 'deactivate', status: 'inactive');
    }

    public static function delete(): self
    {
        return new self(action: 'delete', status: 'deleted');
    }

    public static function restore(): self
    {
        return new self(action: 'restore', status: 'inactive');
    }
}
