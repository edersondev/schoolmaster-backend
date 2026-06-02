<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

final class LifecycleTransitionService
{
    public function transition(Model $resource, LifecycleInput $input, ?callable $activationValidator = null): Model
    {
        return DB::transaction(function () use ($resource, $input, $activationValidator): Model {
            $status = (string) ($resource->getAttribute('status') ?? 'inactive');
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($resource), true);
            $isDeleted = $status === 'deleted' || ($usesSoftDeletes && method_exists($resource, 'trashed') && $resource->trashed());

            match ($input->action) {
                'activate' => $this->activate($resource, $status, $isDeleted, $activationValidator),
                'deactivate' => $this->deactivate($resource, $status, $isDeleted),
                'delete' => $this->delete($resource, $isDeleted),
                'restore' => $this->restore($resource, $isDeleted),
                default => throw new ConflictException('Teacher workflow lifecycle transition is not supported.'),
            };

            return $resource->refresh();
        });
    }

    private function activate(Model $resource, string $status, bool $isDeleted, ?callable $activationValidator): void
    {
        if ($isDeleted) {
            throw new ConflictException('Deleted records must be restored before activation.');
        }

        if ($status === 'active') {
            throw new ConflictException('Resource is already active.');
        }

        $activationValidator?->call($this, $resource);

        $resource->forceFill(['status' => 'active'])->save();
    }

    private function deactivate(Model $resource, string $status, bool $isDeleted): void
    {
        if ($isDeleted) {
            throw new ConflictException('Deleted records must be restored before deactivation.');
        }

        if ($status === 'inactive') {
            throw new ConflictException('Resource is already inactive.');
        }

        $resource->forceFill(['status' => 'inactive'])->save();
    }

    private function delete(Model $resource, bool $isDeleted): void
    {
        if ($isDeleted) {
            throw new ConflictException('Resource is already deleted.');
        }

        $resource->forceFill(['status' => 'deleted'])->save();

        if (in_array(SoftDeletes::class, class_uses_recursive($resource), true) && method_exists($resource, 'delete')) {
            $resource->delete();
        }
    }

    private function restore(Model $resource, bool $isDeleted): void
    {
        if (! $isDeleted) {
            throw new ConflictException('Only deleted records can be restored.');
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($resource), true) && method_exists($resource, 'restore')) {
            $resource->restore();
        }

        $resource->forceFill(['status' => 'inactive'])->save();
    }
}
