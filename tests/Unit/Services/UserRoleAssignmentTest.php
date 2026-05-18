<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\School;
use App\Services\TenantContextService;
use App\Services\Users\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_assignment_requires_same_school_active_role(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $role = Role::query()->create([
            'school_id' => $otherSchool->id,
            'scope' => 'school',
            'name' => 'Other School Role',
        ]);

        $service = new UserService(new TenantContextService);

        $this->expectException(ValidationException::class);

        $service->activeSchoolRoles([$role->uuid], $school->id);
    }
}
