<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\AuthLockoutException;
use App\Services\LoginAttemptControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginAttemptControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_locks_after_five_failures_by_email_or_ip(): void
    {
        $service = app(LoginAttemptControlService::class);

        for ($i = 0; $i < 5; $i++) {
            $service->recordFailure('locked@example.com', '127.0.0.1');
        }

        $this->expectException(AuthLockoutException::class);

        $service->assertNotLocked('locked@example.com', '127.0.0.1');
    }
}
