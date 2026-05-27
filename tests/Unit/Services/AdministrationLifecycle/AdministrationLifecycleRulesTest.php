<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AdministrationLifecycle;

use App\Exceptions\ConflictException;
use App\Models\User;
use App\Services\AdministrationLifecycle\LifecycleAction;
use App\Services\AdministrationLifecycle\LifecycleTransitionRules;
use PHPUnit\Framework\TestCase;

final class AdministrationLifecycleRulesTest extends TestCase
{
    public function test_duplicate_deactivation_is_rejected(): void
    {
        $this->expectException(ConflictException::class);

        $user = new User(['status' => 'inactive']);

        (new LifecycleTransitionRules)->assertTransitionAllowed($user, LifecycleAction::DEACTIVATE);
    }
}
