<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiRoutePrefixTest extends TestCase
{
    public function test_product_routes_use_api_v1_prefix(): void
    {
        $productRoutes = collect(Route::getRoutes())->filter(
            fn ($route) => str_contains((string) $route->getName(), 'api.v1.')
        );

        $this->assertNotEmpty($productRoutes);
        $productRoutes->each(fn ($route) => $this->assertStringStartsWith('api/v1', $route->uri()));
    }
}
