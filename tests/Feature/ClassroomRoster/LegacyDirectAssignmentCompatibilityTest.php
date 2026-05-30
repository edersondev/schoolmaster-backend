<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class LegacyDirectAssignmentCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_foundation_does_not_add_new_direct_student_assignment_routes(): void
    {
        $routes = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertContains('api/v1/learning-sets', $routes);
        $this->assertContains('api/v1/student/learning-sets', $routes);
        $this->assertNotContains('api/v1/direct-assignments', $routes);
        $this->assertNotContains('api/v1/learning-set-assignments', $routes);
    }
}
