<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_success_and_error_envelopes_are_used(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view']);
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users/'.fake()->uuid())
            ->assertNotFound()
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }
}
