<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class AccountLifecyclePermissionProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_seeder_provisions_both_lifecycle_scopes_idempotently(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());

        $this->seed(PermissionSeeder::class);
        $this->seed(PermissionSeeder::class);

        $permissions = Permission::query()
            ->where('code', 'account_lifecycle.manage')
            ->orderBy('scope')
            ->get(['code', 'scope', 'status']);

        $this->assertCount(2, $permissions);
        $this->assertSame(['platform', 'school'], $permissions->pluck('scope')->all());
        $this->assertSame(['active', 'active'], $permissions->pluck('status')->all());
    }

    public function test_mysql_rejects_duplicate_permission_code_and_scope(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());

        Permission::query()->create([
            'code' => 'account_lifecycle.manage',
            'name' => 'Manage platform account lifecycle',
            'scope' => 'platform',
            'status' => 'active',
        ]);

        $this->expectException(QueryException::class);

        Permission::query()->create([
            'code' => 'account_lifecycle.manage',
            'name' => 'Duplicate platform lifecycle permission',
            'scope' => 'platform',
            'status' => 'active',
        ]);
    }

    public function test_rollback_refuses_to_restore_code_only_uniqueness_when_scoped_duplicates_exist(): void
    {
        $this->seed(PermissionSeeder::class);

        $migration = require database_path('migrations/2026_08_11_000001_make_permission_codes_scope_unique.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot restore code-only permission uniqueness');

        $migration->down();
    }
}
