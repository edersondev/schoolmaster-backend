<?php

declare(strict_types=1);

namespace App\Services\Guardians;

use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GuardianUserLinkService
{
    use AuthorizesSchoolAdministration;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function create(User $actor, TenantContext $context, string $guardianUuid, string $userUuid, ?string $note = null): GuardianUserLink
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'guardians.manage');

        return DB::transaction(function () use ($actor, $school, $guardianUuid, $userUuid, $note): GuardianUserLink {
            $guardian = $this->activeGuardian($guardianUuid, $school->id);
            $user = $this->activeUser($userUuid, $school->id);

            $duplicate = GuardianUserLink::query()
                ->where('school_id', $school->id)
                ->where('guardian_id', $guardian->id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($duplicate) {
                throw new ConflictException('An active guardian-user link already exists for this guardian and user.');
            }

            try {
                return GuardianUserLink::query()->create([
                    'school_id' => $school->id,
                    'guardian_id' => $guardian->id,
                    'user_id' => $user->id,
                    'created_by_user_id' => $actor->id,
                    'creation_note' => $note,
                    'status' => 'active',
                ])->load(['school', 'guardian', 'user']);
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23000') {
                    throw new ConflictException('An active guardian-user link already exists for this guardian and user.', previous: $exception);
                }

                throw $exception;
            }
        });
    }

    public function deactivate(User $actor, TenantContext $context, string $guardianUuid, string $linkUuid, ?string $reason = null): GuardianUserLink
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'guardians.manage');

        return DB::transaction(function () use ($school, $guardianUuid, $linkUuid, $reason): GuardianUserLink {
            $guardian = $this->activeGuardian($guardianUuid, $school->id);
            $link = GuardianUserLink::query()
                ->where('uuid', $linkUuid)
                ->where('school_id', $school->id)
                ->where('guardian_id', $guardian->id)
                ->first();

            if ($link === null) {
                throw (new ModelNotFoundException)->setModel(GuardianUserLink::class);
            }

            if ($link->status !== 'active') {
                throw ValidationException::withMessages([
                    'guardian_user_link_id' => ['The guardian-user link is not active.'],
                ]);
            }

            $link->forceFill([
                'status' => 'inactive',
                'deactivated_at' => now(),
                'deactivation_reason' => $reason,
            ])->save();

            return $link->load(['school', 'guardian', 'user']);
        });
    }

    private function activeGuardian(string $guardianUuid, int $schoolId): Guardian
    {
        $guardian = Guardian::query()
            ->where('uuid', $guardianUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($guardian === null) {
            throw (new ModelNotFoundException)->setModel(Guardian::class);
        }

        return $guardian;
    }

    private function activeUser(string $userUuid, int $schoolId): User
    {
        $user = User::query()
            ->where('uuid', $userUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'user_id' => ['The user must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $user;
    }
}
