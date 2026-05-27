<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_guardian(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['guardians.view', 'guardians.manage']);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Old Guardian', 'relationship_type' => 'parent']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/guardians/{$guardian->uuid}", ['full_name' => 'New Guardian'])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'New Guardian');
    }
}
