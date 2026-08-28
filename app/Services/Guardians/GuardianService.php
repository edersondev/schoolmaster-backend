<?php

declare(strict_types=1);

namespace App\Services\Guardians;

use App\DTOs\Guardians\CreateGuardianData;
use App\DTOs\Guardians\GuardianListFilters;
use App\DTOs\TenantContext;
use App\Models\Guardian;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GuardianService
{
    use AuthorizesSchoolAdministration;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, GuardianListFilters $filters): LengthAwarePaginator
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'guardians.view');

        return Guardian::query()
            ->with('school')
            ->where('school_id', $school->id)
            ->when($filters->fullName !== null, fn (Builder $query): Builder => $this->whereContainsNormalized($query, 'full_name', $filters->fullName))
            ->when($filters->contactEmail !== null, fn (Builder $query): Builder => $this->whereContainsNormalized($query, 'contact_email', $filters->contactEmail))
            ->when($filters->status !== null, fn (Builder $query): Builder => $query->where('status', $filters->status))
            ->orderBy('full_name')
            ->paginate($filters->perPage);
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
                            'uuid' => (string) Str::uuid(),
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

    /**
     * @param  Builder<Guardian>  $query
     * @return Builder<Guardian>
     */
    private function whereContainsNormalized(Builder $query, string $column, string $value): Builder
    {
        $escaped = addcslashes(mb_strtolower($value), '\\%_');

        return $query->whereRaw(
            sprintf('LOWER(%s) COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE ?', $query->getQuery()->getGrammar()->wrap($column)),
            ['%'.$escaped.'%', '\\'],
        );
    }
}
