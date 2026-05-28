<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_account_admin_cannot_manage_school_scoped_account(): void
    {
        $school = School::factory()->create();
        $platformAdmin = $this->createPlatformUser(['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($platformAdmin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-lock", [
                'reason' => 'Not allowed',
            ])
            ->assertForbidden();
    }
}
