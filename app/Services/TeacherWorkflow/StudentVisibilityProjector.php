<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use Illuminate\Support\Collection;

final class StudentVisibilityProjector
{
    public function isVisible(string $status): bool
    {
        return $status === 'active';
    }

    /**
     * @param  iterable<object|array<string, mixed>>  $records
     * @return Collection<int, object|array<string, mixed>>
     */
    public function visible(iterable $records): Collection
    {
        return collect($records)->filter(function (mixed $record): bool {
            $status = is_array($record) ? ($record['status'] ?? null) : ($record->status ?? null);

            return $status === 'active';
        })->values();
    }
}
