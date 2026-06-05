<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportRun;
use App\Models\School;
use App\Models\User;

final class ReportLifecyclePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('reports.view', $school->id);
    }

    public function download(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.view', $reportRun->school_id);
    }

    public function retry(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.lifecycle', $reportRun->school_id);
    }

    public function cancel(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.lifecycle', $reportRun->school_id);
    }

    public function delete(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.lifecycle', $reportRun->school_id);
    }

    public function restore(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.lifecycle', $reportRun->school_id);
    }
}
