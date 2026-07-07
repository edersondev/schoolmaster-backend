<?php

declare(strict_types=1);

namespace App\Services\School;

use App\DTOs\School\SchoolProfileData;
use App\Models\School;
use App\Services\Addresses\SchoolAddressService;
use App\Services\AdministrationLifecycle\AdministrationLifecycleService;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Support\Facades\DB;

final class SchoolProfileService
{
    public function __construct(
        private readonly SchoolAddressService $addresses,
        private readonly SchoolLogoService $logos,
        private readonly AdministrationLifecycleService $lifecycle,
    ) {}

    public function create(SchoolProfileData $data): School
    {
        return DB::transaction(function () use ($data): School {
            $school = School::query()->create($data->schoolAttributes(includeDocument: true, includeDefaults: true));
            $this->addresses->applySubmittedAddress($school, ['address' => $data->address]);

            if ($data->logoFile !== null) {
                $school->logo_path = $this->logos->store($data->logoFile, $school);
                $school->save();
            }

            return $school->refresh()->load('address');
        });
    }

    public function update(School $school, SchoolProfileData $data): School
    {
        return DB::transaction(function () use ($school, $data): School {
            $previousLogo = $school->logo_path;
            $this->assertStatusTransitionAllowed($school, $data->status);

            $school->fill($data->schoolAttributes(includeDocument: false));
            $this->addresses->applySubmittedAddress($school, ['address' => $data->address]);

            if ($data->logoFile !== null) {
                $school->logo_path = $this->logos->store($data->logoFile, $school);
            }

            $school->save();

            if ($data->logoFile !== null) {
                $this->logos->delete($previousLogo);
            }

            return $school->refresh()->load('address');
        });
    }

    private function assertStatusTransitionAllowed(School $school, ?int $status): void
    {
        if ($status === null) {
            return;
        }

        $current = $school->status;
        $target = $status === 1 ? 'active' : 'inactive';

        if ($current === $target || (string) $current === (string) $status) {
            return;
        }

        $this->lifecycle->assertTransitionEligibility(
            $school,
            $status === 1 ? LifecycleAction::ACTIVATE : LifecycleAction::DEACTIVATE,
        );
    }
}
