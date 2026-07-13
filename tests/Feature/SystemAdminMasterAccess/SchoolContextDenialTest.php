<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SchoolContextDenialTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_unknown_and_inactive_context_fail_before_school_owned_lookup(): void
    {
        $inactive = School::factory()->inactive()->create();
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)
            ->getJson('/api/v1/academic-years')
            ->assertForbidden();

        foreach ([(string) Str::uuid(), $inactive->uuid] as $schoolId) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $schoolId)
                ->getJson('/api/v1/users/'.Str::uuid())
                ->assertForbidden()
                ->assertJsonPath('error.code', 'tenant_mismatch');
        }
    }
}
