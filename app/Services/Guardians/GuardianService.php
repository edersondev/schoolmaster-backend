<?php

declare(strict_types=1);

namespace App\Services\Guardians;

use App\DTOs\Guardians\CreateGuardianData;
use App\DTOs\TenantContext;
use App\Models\Guardian;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GuardianService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQuery($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'guardians.view');

        return Guardian::query()
            ->with('school')
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('full_name')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateGuardianData $data): Guardian
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'guardians.manage');
        $studentProfiles = $this->activeStudentProfiles($data->studentProfileIds, $school->id);

        return DB::transaction(function () use ($data, $school, $studentProfiles): Guardian {
            $guardian = Guardian::query()->create([
                'school_id' => $school->id,
                'full_name' => $data->fullName,
                'relationship_type' => $data->relationshipType,
                'contact_email' => $data->contactEmail,
                'contact_phone' => $data->contactPhone,
                'status' => 'active',
            ]);

            if ($studentProfiles->isNotEmpty()) {
                $guardian->studentProfiles()->sync(
                    $studentProfiles->mapWithKeys(fn (StudentProfile $profile): array => [
                        $profile->id => [
                            'uuid' => (string) \Illuminate\Support\Str::uuid(),
                            'school_id' => $school->id,
                            'relationship_type' => $data->relationshipType,
                            'status' => 'active',
                        ],
                    ])->all(),
                );
            }

            return $guardian->load('school');
        });
    }

    /**
     * @param  array<int, string>  $studentProfileUuids
     * @return Collection<int, StudentProfile>
     */
    private function activeStudentProfiles(array $studentProfileUuids, int $schoolId): Collection
    {
        if ($studentProfileUuids === []) {
            return new Collection;
        }

        $profiles = StudentProfile::query()
            ->whereIn('uuid', $studentProfileUuids)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->get();

        if ($profiles->count() !== count(array_unique($studentProfileUuids))) {
            throw ValidationException::withMessages([
                'student_profile_ids' => ['All student profiles must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $profiles;
    }
}
