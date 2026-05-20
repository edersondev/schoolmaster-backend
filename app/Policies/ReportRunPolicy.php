<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportRun;
use App\Models\School;
use App\Models\User;

final class ReportRunPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('reports.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('reports.request', $school->id);
    }

    public function download(User $user, ReportRun $reportRun): bool
    {
        return $user->hasSchoolPermission('reports.view', $reportRun->school_id);
    }
}
