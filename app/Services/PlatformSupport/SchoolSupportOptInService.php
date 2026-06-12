<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\TargetSchoolSupportOptIn;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class SchoolSupportOptInService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, string $schoolUuid, array $data): TargetSchoolSupportOptIn
    {
        return DB::transaction(function () use ($actor, $schoolUuid, $data): TargetSchoolSupportOptIn {
            $school = $this->resolveSchool($schoolUuid);
            $this->authorization->authorizeSupportOptIn($actor, $school);

            $activeExists = TargetSchoolSupportOptIn::query()
                ->where('school_id', $school->id)
                ->where('state', 'approved')
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeExists) {
                throw new ConflictException('An active support opt-in already exists for this school.');
            }

            $optIn = TargetSchoolSupportOptIn::query()->create([
                'school_id' => $school->id,
                'requested_by_user_id' => $actor->id,
                'approved_by_user_id' => $actor->id,
                'state' => 'approved',
                'reason_code' => $data['reason_code'],
                'purpose' => $data['purpose'],
                'correlation_id' => $data['correlation_id'],
                'approved_at' => now(),
                'expires_at' => now()->addDay(),
            ]);

            $this->audit->record($actor, 'support_opt_in_created', 'allowed', $optIn->reason_code, $optIn->correlation_id, $school, targetSchoolSupportOptIn: $optIn);

            return $optIn->load(['school', 'approvedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function revoke(User $actor, string $schoolUuid, string $optInUuid, array $data): TargetSchoolSupportOptIn
    {
        return DB::transaction(function () use ($actor, $schoolUuid, $optInUuid, $data): TargetSchoolSupportOptIn {
            $school = $this->resolveSchool($schoolUuid);
            $this->authorization->authorizeSupportOptIn($actor, $school);

            $optIn = TargetSchoolSupportOptIn::query()
                ->where('uuid', $optInUuid)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->first();

            if ($optIn === null) {
                throw (new ModelNotFoundException)->setModel(TargetSchoolSupportOptIn::class);
            }

            if ($optIn->state !== 'approved') {
                throw new ConflictException('Only approved support opt-ins can be revoked.');
            }

            $optIn->update([
                'state' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason_code' => $data['reason_code'],
            ]);

            $this->audit->record($actor, 'support_opt_in_revoked', 'revoked', $data['reason_code'], $data['correlation_id'], $school, targetSchoolSupportOptIn: $optIn);

            return $optIn->refresh()->load(['school', 'approvedBy']);
        });
    }

    private function resolveSchool(string $schoolUuid): School
    {
        $school = School::query()->where('uuid', $schoolUuid)->first();

        if ($school === null) {
            throw (new ModelNotFoundException)->setModel(School::class);
        }

        return $school;
    }
}
