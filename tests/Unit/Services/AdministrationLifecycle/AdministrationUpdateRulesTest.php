<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AdministrationLifecycle;

use App\Services\AdministrationLifecycle\AdministrationResourceRegistry;
use PHPUnit\Framework\TestCase;

final class AdministrationUpdateRulesTest extends TestCase
{
    public function test_user_update_mutable_fields_exclude_tenant_ownership(): void
    {
        $config = (new AdministrationResourceRegistry)->config('users');

        $this->assertContains('full_name', $config['mutable']);
        $this->assertNotContains('school_id', $config['mutable']);
    }
}
