<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_is_non_enumerating_for_eligible_and_missing_accounts(): void
    {
        $school = School::factory()->create();
        User::factory()->create([
            'school_id' => $school->id,
            'email' => 'reset@example.test',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/password-reset-requests', [
            'email' => 'reset@example.test',
            'school_id' => $school->uuid,
        ])->assertAccepted()
            ->assertJsonPath('data.accepted', true);

        $this->postJson('/api/v1/auth/password-reset-requests', [
            'email' => 'missing@example.test',
            'school_id' => $school->uuid,
        ])->assertAccepted()
            ->assertJsonPath('data.accepted', true);

        $this->assertSame(1, PasswordResetRequest::query()->whereNotNull('token_hash')->count());
    }
}
