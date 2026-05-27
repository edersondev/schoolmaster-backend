<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class AccountLifecyclePolicy
{
    public function manage(User $actor, string $scope, ?School $school = null): bool
    {
        if ($actor->status !== 'active') {
            return false;
        }

        if ($scope === 'platform') {
            return $actor->isPlatformUser()
                && $actor->hasPermission('account_lifecycle.manage', 'platform');
        }

        if ($scope !== 'school' || $school === null || $school->status !== 'active') {
            return false;
        }

        return $actor->school_id === $school->id
            && $actor->hasSchoolPermission('account_lifecycle.manage', $school->id);
    }
}
