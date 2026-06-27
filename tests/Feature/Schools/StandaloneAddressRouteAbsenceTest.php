<?php

declare(strict_types=1);

namespace Tests\Feature\Schools;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class StandaloneAddressRouteAbsenceTest extends TestCase
{
    public function test_no_standalone_address_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertNotContains('api/v1/addresses', $uris);
        $this->assertNotContains('api/v1/addresses/{address}', $uris);
    }
}
