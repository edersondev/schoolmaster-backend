<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_deactivate_guardian(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['guardians.view', 'guardians.lifecycle']);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian', 'relationship_type' => 'parent', 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/guardians/{$guardian->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
