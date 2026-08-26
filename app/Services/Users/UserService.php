<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\DTOs\TenantContext;
use App\DTOs\Users\CreateUserData;
use App\Exceptions\RecoverableUserConflictException;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Policies\AdministrationLifecyclePolicy;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UserService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly IdentityEmailService $identityEmails,
        private readonly AdministrationLifecyclePolicy $lifecyclePolicy,
        private readonly DuplicateEmailAuditService $duplicateEmailAudit,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQuery($query, ['full_name', 'email', 'created_at']);

        if ($context->school === null) {
            if (! $actor->hasPermission('schools.view', 'platform')) {
                throw new AuthorizationException('The authenticated user lacks permission for this action.');
            }

            return User::query()
                ->with(['roles.permissions', 'roles.school', 'school'])
                ->whereNull('school_id')
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->tap(fn ($query) => $this->applySorts($query, $filters))
                ->paginate((int) ($filters['per_page'] ?? 25));
        }

        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'users.view');

        return User::query()
            ->with(['roles.permissions', 'roles.school', 'school'])
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->tap(fn ($query) => $this->applySorts($query, $filters))
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateUserData $data, ?string $sourceIp = null): User
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'users.manage');

        if ($data->schoolId !== null && $data->schoolId !== $school->uuid) {
            throw ValidationException::withMessages(['school_id' => ['The school_id must match the resolved tenant context.']]);
        }

        $roles = $this->activeSchoolRoles($data->roleIds, $school->id);
        $canonicalEmail = $this->identityEmails->normalize($data->email);
        $decision = $this->identityEmails->decide($canonicalEmail);

        if (! $decision->isAvailable()) {
            if ($decision->owner !== null && $this->lifecyclePolicy->canDiscloseDuplicateEmailRecovery($actor, $school, $decision->owner)) {
                $this->duplicateEmailAudit->record(
                    $actor,
                    $school,
                    'school',
                    'direct_user_creation',
                    'recoverable_user_conflict',
                    $canonicalEmail,
                    'recoverable_user_conflict',
                    $sourceIp,
                    $decision->owner,
                );

                throw new RecoverableUserConflictException($decision->owner->uuid);
            }

            $this->rejectUnavailableEmail($actor, $school, $canonicalEmail, 'email_unavailable', $sourceIp);
        }

        try {
            return DB::transaction(function () use ($data, $school, $roles, $canonicalEmail): User {
                $user = User::query()->create([
                    'school_id' => $school->id,
                    'name' => $data->fullName,
                    'full_name' => $data->fullName,
                    'email' => $canonicalEmail,
                    'password' => Str::password(32),
                    'status' => $data->accountSetupMode === 'invitation' ? 'invited' : 'active',
                ]);
                $user->roles()->sync($roles->pluck('id')->all());

                return $user->load(['roles.permissions', 'roles.school', 'school']);
            });
        } catch (Throwable $exception) {
            if (! $this->identityEmails->isEmailUniqueViolation($exception)) {
                throw $exception;
            }

            $this->rejectUnavailableEmail($actor, $school, $canonicalEmail, 'persistence_conflict', $sourceIp);
        }
    }

    /**
     * @param  array<int, string>  $roleUuids
     * @return Collection<int, Role>
     */
    public function activeSchoolRoles(array $roleUuids, int $schoolId): Collection
    {
        $roles = Role::query()
            ->whereIn('uuid', $roleUuids)
            ->where('status', 'active')
            ->where('scope', 'school')
            ->where('school_id', $schoolId)
            ->get();

        if ($roles->count() !== count(array_unique($roleUuids))) {
            throw ValidationException::withMessages([
                'role_ids' => ['All roles must exist, be active, school-scoped, and belong to the resolved school.'],
            ]);
        }

        return $roles;
    }

    /**
     * @param  array<int, string>  $roleUuids
     * @return Collection<int, Role>
     */
    public function activePlatformRoles(array $roleUuids): Collection
    {
        $roles = Role::query()
            ->whereIn('uuid', $roleUuids)
            ->where('status', 'active')
            ->where('scope', 'platform')
            ->whereNull('school_id')
            ->get();

        if ($roles->count() !== count(array_unique($roleUuids))) {
            throw ValidationException::withMessages([
                'role_ids' => ['All roles must exist, be active, platform-scoped, and have no school ownership.'],
            ]);
        }

        return $roles;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySorts($query, array $filters): void
    {
        foreach ($this->parseSorts($filters['sort'] ?? null, ['full_name', 'email', 'created_at'], 'full_name') as $sort) {
            $query->orderBy($sort['field'], $sort['direction']);
        }

        $query->orderBy('id');
    }

    private function rejectUnavailableEmail(User $actor, School $school, string $canonicalEmail, string $reasonCode, ?string $sourceIp): never
    {
        $this->duplicateEmailAudit->record(
            $actor,
            $school,
            'school',
            'direct_user_creation',
            'validation_failed',
            $canonicalEmail,
            $reasonCode,
            $sourceIp,
        );

        throw ValidationException::withMessages(['email' => ['The email is unavailable.']]);
    }
}
