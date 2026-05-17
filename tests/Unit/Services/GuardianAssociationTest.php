<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Guardians\CreateGuardianData;
use App\DTOs\TenantContext;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Guardians\GuardianService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class GuardianAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_association_rejects_cross_tenant_student_profile(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $actor = $this->createSchoolAdmin($school);
        $student = User::factory()->create(['school_id' => $otherSchool->id]);
        $profile = StudentProfile::query()->create(['school_id' => $otherSchool->id, 'user_id' => $student->id]);
        $service = new GuardianService(new TenantContextService);

        $this->expectException(ValidationException::class);

        $service->create(
            $actor,
            new TenantContext($school, 'test', 'resolved'),
            new CreateGuardianData('Invalid', 'parent', null, null, [$profile->uuid]),
        );
    }
}
