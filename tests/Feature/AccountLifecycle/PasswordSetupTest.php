<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountInvitation;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PasswordSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_completes_first_password_setup_and_can_authenticate(): void
    {
        $school = School::factory()->create();
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'School User',
        ]);
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'setup@example.test',
            'password' => Hash::make('temporary-secret'),
            'status' => 'invited',
        ]);
        $user->roles()->attach($role);
        $plainToken = 'invitation-token-value-with-enough-length-123';

        AccountInvitation::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'scope' => 'school',
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $this->postJson("/api/v1/account-invitations/{$plainToken}/setup", [
            'password' => 'correct-horse-battery-staple',
        ])->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'setup@example.test',
            'password' => 'correct-horse-battery-staple',
            'school_id' => $school->uuid,
        ])->assertOk();
    }
}
