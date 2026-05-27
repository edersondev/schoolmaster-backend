<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AdministrationLifecycle;

use App\Services\AdministrationLifecycle\LifecycleAction;
use PHPUnit\Framework\TestCase;

final class BulkLifecycleServiceTest extends TestCase
{
    public function test_bulk_limit_is_bounded(): void
    {
        $this->assertSame(50, LifecycleAction::MAX_BULK_RECORDS);
    }
}
