<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class PlatformSupportPolicy
{
    public function viewSchoolSummaries(User $user): bool
    {
        return $this->activePlatformActor($user)
            && $user->hasPermission('platform_support.overview', 'platform');
    }

    public function viewReportingOverview(User $user): bool
    {
        return $this->activePlatformActor($user)
            && $user->hasPermission('platform_support.reporting', 'platform');
    }

    public function requestSupportAccess(User $user): bool
    {
        return $this->activePlatformActor($user)
            && $user->hasPermission('platform_support.drill_down', 'platform');
    }

    public function viewSupportDecision(User $user): bool
    {
        return $this->requestSupportAccess($user);
    }

    public function viewSupportDiagnostics(User $user): bool
    {
        return $this->requestSupportAccess($user);
    }

    public function approveSupportAccess(User $user): bool
    {
        return $this->activePlatformActor($user)
            && $user->hasPermission('platform_support.approve', 'platform');
    }

    public function revokeSupportAccess(User $user): bool
    {
        return $this->approveSupportAccess($user);
    }

    public function createSchoolSupportOptIn(User $user, School $school): bool
    {
        return $user->isActive()
            && $user->school_id === $school->id
            && $user->hasSchoolPermission('platform_support.opt_in', $school->id);
    }

    public function revokeSchoolSupportOptIn(User $user, School $school): bool
    {
        return $this->createSchoolSupportOptIn($user, $school);
    }

    public function viewAuditEvents(User $user): bool
    {
        return $this->activePlatformActor($user)
            && $user->hasPermission('platform_support.audit', 'platform');
    }

    private function activePlatformActor(User $user): bool
    {
        return $user->isActive() && $user->isPlatformUser();
    }
}
