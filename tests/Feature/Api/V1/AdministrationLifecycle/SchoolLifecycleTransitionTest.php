<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_deactivate_restore_and_soft_delete_school(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createPlatformUser(['schools.view', 'schools.manage', 'schools.lifecycle']));

        $this->withToken($token)->postJson("/api/v1/schools/{$school->uuid}/deactivate", [
            'effective_at' => '2026-05-26',
            'reason' => 'maintenance',
        ])->assertOk()->assertJsonPath('data.action', 'deactivate');

        $this->withToken($token)->deleteJson("/api/v1/schools/{$school->uuid}", [
            'effective_at' => '2026-05-26',
            'reason' => 'archive',
        ])->assertOk()->assertJsonPath('data.action', 'delete');

        $this->withToken($token)->postJson("/api/v1/schools/{$school->uuid}/restore", [
            'effective_at' => '2026-05-26',
            'reason' => 'restore',
        ])->assertOk()->assertJsonPath('data.action', 'restore');
    }
}
