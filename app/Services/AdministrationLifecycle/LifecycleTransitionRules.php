<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\Exceptions\ConflictException;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LifecycleTransitionRules
{
    public function assertTransitionAllowed(Model $resource, string $action): void
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($resource), true);
        $isDeleted = $usesSoftDeletes && method_exists($resource, 'trashed') && $resource->trashed();
        $status = $this->statusName($resource);

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

    public function statusAfter(Model $resource, string $action): string|int|null
    {
        return match ($action) {
            LifecycleAction::ACTIVATE => $resource instanceof School ? School::STATUS_ACTIVE : 'active',
            LifecycleAction::DEACTIVATE => $resource instanceof School ? School::STATUS_INACTIVE : 'inactive',
            LifecycleAction::DELETE => $resource->getAttribute('status') ?? ($resource instanceof School ? School::STATUS_ACTIVE : 'active'),
            LifecycleAction::RESTORE => $resource->getAttribute('status') ?? ($resource instanceof School ? School::STATUS_ACTIVE : 'active'),
            default => null,
        };
    }

    private function statusName(Model $resource): string
    {
        if ($resource instanceof School) {
            return $resource->isActive() ? 'active' : 'inactive';
        }

        return (string) ($resource->getAttribute('status') ?? 'active');
    }
}
