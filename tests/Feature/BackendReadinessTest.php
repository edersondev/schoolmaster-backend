<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class BackendReadinessTest extends TestCase
{
    public function test_api_route_is_registered_and_welcome_route_is_removed(): void
    {
        $this->get('/api/v1/health')->assertOk()->assertJson(['status' => 'ok']);
        $this->get('/')->assertNotFound();
    }
}
