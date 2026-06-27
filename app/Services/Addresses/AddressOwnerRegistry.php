<?php

declare(strict_types=1);

namespace App\Services\Addresses;

use App\Models\School;
use InvalidArgumentException;

final class AddressOwnerRegistry
{
    /**
     * @return list<class-string>
     */
    public function approvedOwners(): array
    {
        return [School::class];
    }

    public function assertApproved(object $owner): void
    {
        if (! $owner instanceof School) {
            throw new InvalidArgumentException('Address owner is not approved for this feature slice.');
        }
    }
}
