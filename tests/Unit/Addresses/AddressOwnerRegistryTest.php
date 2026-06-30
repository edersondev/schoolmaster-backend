<?php

declare(strict_types=1);

namespace Tests\Unit\Addresses;

use App\Models\School;
use App\Models\User;
use App\Services\Addresses\AddressOwnerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AddressOwnerRegistryTest extends TestCase
{
    public function test_registry_accepts_only_approved_polymorphic_address_owners(): void
    {
        $registry = new AddressOwnerRegistry();

        $registry->assertApproved(new School());

        $this->expectException(InvalidArgumentException::class);
        $registry->assertApproved(new User());
    }
}
