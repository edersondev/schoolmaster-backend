<?php

declare(strict_types=1);

namespace Tests\Unit\Addresses;

use App\Models\School;
use App\Models\User;
use App\Services\Addresses\AddressOwnerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AddressAuthorizationBoundaryTest extends TestCase
{
    public function test_owner_boundary_is_centralized_in_registry(): void
    {
        $registry = new AddressOwnerRegistry();

        $this->assertSame([School::class], $registry->approvedOwners());

        $this->expectException(InvalidArgumentException::class);
        $registry->assertApproved(new User());
    }
}
