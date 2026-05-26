<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationDetailUpdateContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_and_update_use_success_and_validation_envelopes(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$user->uuid}", ['school_id' => $school->uuid])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }
}
