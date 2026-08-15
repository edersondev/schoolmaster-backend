<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class AccountLifecyclePolicy
{
    public function manage(User $actor, string $scope, ?School $school = null, ?User $target = null): bool
    {
        if (! $actor->isActive() || ($target !== null && $actor->is($target))) {
            return false;
        }

        if ($scope === 'platform') {
            return $actor->isSystemAdministrator()
                || ($actor->isPlatformUser()
                    && $actor->hasPermission('account_lifecycle.manage', 'platform'));
        }

        if ($scope !== 'school' || $school === null || ! $school->isActive()) {
            return false;
        }

        return $actor->isSystemAdministrator()
            || ($actor->school_id === $school->id
                && $actor->hasSchoolPermission('account_lifecycle.manage', $school->id));
    }
}
