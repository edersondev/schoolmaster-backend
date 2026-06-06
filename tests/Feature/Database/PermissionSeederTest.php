<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_permissions_include_lifecycle_and_definition_management(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'code' => 'reports.lifecycle',
            'scope' => 'school',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'reports.definitions.manage',
            'scope' => 'school',
            'status' => 'active',
        ]);
    }
}
