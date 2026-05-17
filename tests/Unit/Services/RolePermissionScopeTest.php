<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Services\Roles\RoleService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class RolePermissionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_permissions_must_match_requested_scope(): void
    {
        $permission = Permission::query()->create([
            'code' => 'platform.only',
            'name' => 'Platform Only',
            'scope' => 'platform',
        ]);

        $service = new RoleService(new TenantContextService);

        $this->expectException(ValidationException::class);

        $service->activePermissions([$permission->uuid], 'school');
    }
}
