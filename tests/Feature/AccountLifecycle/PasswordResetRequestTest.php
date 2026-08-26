<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountLock;
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

    public function test_reset_request_response_is_identical_for_every_delivery_related_eligibility_state(): void
    {
        $school = School::factory()->create();
        $active = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'public-active@example.test',
            'status' => 'active',
        ]);
        User::factory()->create([
            'school_id' => $school->id,
            'email' => 'public-inactive@example.test',
            'status' => 'inactive',
        ]);
        User::factory()->create([
            'school_id' => $school->id,
            'email' => 'public-invited@example.test',
            'status' => 'invited',
        ]);
        $locked = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'public-locked@example.test',
            'status' => 'active',
        ]);
        AccountLock::query()->create([
            'user_id' => $locked->id,
            'school_id' => $school->id,
            'lock_type' => 'administrative',
            'status' => 'active',
            'locked_at' => now(),
        ]);
        $deleted = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'public-deleted@example.test',
            'status' => 'active',
        ]);
        $deleted->delete();
        $expectedBody = null;

        foreach ([
            $active->email,
            'public-missing@example.test',
            'public-inactive@example.test',
            'public-invited@example.test',
            'public-locked@example.test',
            'public-deleted@example.test',
        ] as $email) {
            $response = $this->postJson('/api/v1/auth/password-reset-requests', [
                'email' => $email,
                'school_id' => $school->uuid,
            ])->assertAccepted()
                ->assertJsonPath('data.accepted', true);

            $expectedBody ??= $response->getContent();
            $this->assertSame($expectedBody, $response->getContent());
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $response = $this->postJson('/api/v1/auth/password-reset-requests', [
                'email' => $active->email,
                'school_id' => $school->uuid,
            ])->assertAccepted();

            $this->assertSame($expectedBody, $response->getContent());
        }
    }
}
