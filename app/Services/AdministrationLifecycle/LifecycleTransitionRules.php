<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LifecycleTransitionRules
{
    public function assertTransitionAllowed(Model $resource, string $action): void
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($resource), true);
        $isDeleted = $usesSoftDeletes && method_exists($resource, 'trashed') && $resource->trashed();
        $status = (string) ($resource->getAttribute('status') ?? 'active');

        if ($action === LifecycleAction::RESTORE) {
            if (! $isDeleted) {
                throw new ConflictException('Only soft-deleted records can be restored.');
            }

            return;
        }

        if ($isDeleted) {
            throw new ConflictException('Soft-deleted records must be restored before other lifecycle actions.');
        }

        if ($action === LifecycleAction::ACTIVATE && $status === 'active') {
            throw new ConflictException('Resource is already active.');
        }

        if ($action === LifecycleAction::DEACTIVATE && $status === 'inactive') {
            throw new ConflictException('Resource is already inactive.');
        }
    }

    public function statusAfter(Model $resource, string $action): ?string
    {
        return match ($action) {
            LifecycleAction::ACTIVATE => 'active',
            LifecycleAction::DEACTIVATE => 'inactive',
            LifecycleAction::DELETE => (string) ($resource->getAttribute('status') ?? 'active'),
            LifecycleAction::RESTORE => (string) ($resource->getAttribute('status') ?? 'active'),
            default => null,
        };
    }
}
