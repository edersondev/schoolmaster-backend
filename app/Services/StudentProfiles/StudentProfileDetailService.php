<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\TenantContext;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentAdministration;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class StudentProfileDetailService
{
    use AuthorizesStudentAdministration;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function get(User $actor, TenantContext $context, string $studentProfileId): StudentProfile
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertCanViewStudentProfiles($actor, $school);

        $profile = StudentProfile::query()
            ->with(['school', 'user', 'currentAcademicYear', 'guardians.school', 'enrollmentHistories.school', 'enrollmentHistories.studentProfile', 'enrollmentHistories.actor'])
            ->where('uuid', $studentProfileId)
            ->where('school_id', $school->id)
            ->first();

        if ($profile === null) {
            throw new ModelNotFoundException;
        }

        return $profile;
    }
}
