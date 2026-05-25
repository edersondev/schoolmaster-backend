<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\TenantContext;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentAdministration;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class StudentProfileListService
{
    use AuthorizesStudentAdministration;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly StudentProfileListQuery $listQuery,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertCanViewStudentProfiles($actor, $school);

        $builder = StudentProfile::query()
            ->with(['school', 'user'])
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            });

        foreach ($this->listQuery->parseSorts($filters['sort'] ?? null) as $sort) {
            if ($sort['field'] === 'full_name') {
                $builder->orderBy('last_name', $sort['direction'])->orderBy('first_name', $sort['direction']);

                continue;
            }

            $builder->orderBy($sort['field'], $sort['direction']);
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 25));
    }
}
