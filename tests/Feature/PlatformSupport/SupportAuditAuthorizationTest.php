<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportAuditAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_without_audit_review_permission_is_denied(): void
    {
        $actor = $this->createPlatformUser(['platform_support.overview']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/support-audit-events')
            ->assertForbidden()
            ->assertJsonMissingPath('data');
    }
}
