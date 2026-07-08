<?php

declare(strict_types=1);

namespace App\Services\Addresses;

use App\DTOs\Addresses\AddressData;
use App\Models\Address;
use App\Models\School;
use Illuminate\Support\Facades\DB;

final class SchoolAddressService
{
    public function __construct(private readonly AddressOwnerRegistry $owners) {}

    public function createOrReplace(School $school, AddressData $data): Address
    {
        $this->owners->assertApproved($school);

        return DB::transaction(function () use ($school, $data): Address {
            $address = $this->existingAddressFor($school);
            $attributes = [
                ...$data->toArray(),
                'school_id' => $school->id,
                'addressable_type' => School::class,
                'addressable_id' => $school->id,
            ];

            if ($address === null) {
                /** @var Address $address */
                $address = $school->address()->create($attributes);

                return $address;
            }

            if ($address->trashed()) {
                $address->restore();
            }

            $address->fill($attributes);
            $address->save();

            return $address->refresh();
        });
    }

    public function remove(School $school): void
    {
        $this->owners->assertApproved($school);

        $school->address()->delete();
    }

    public function applySubmittedAddress(School $school, array $payload): void
    {
        if (! array_key_exists('address', $payload)) {
            return;
        }

        if ($payload['address'] === null) {
            $this->remove($school);

            return;
        }

        $this->createOrReplace($school, AddressData::fromArray($payload['address']));
    }

    private function existingAddressFor(School $school): ?Address
    {
        /** @var Address|null $address */
        $address = Address::query()
            ->withTrashed()
            ->where('school_id', $school->id)
            ->where('addressable_type', School::class)
            ->where('addressable_id', $school->id)
            ->orderBy('deleted_at')
            ->latest('updated_at')
            ->first();

        return $address;
    }
}
