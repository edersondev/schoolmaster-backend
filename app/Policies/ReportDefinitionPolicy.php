<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportDefinition;
use App\Models\School;
use App\Models\User;

final class ReportDefinitionPolicy
{
    public function viewCatalog(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('reports.definitions.manage', $school->id);
    }

    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('reports.definitions.manage', $school->id);
    }

    public function manage(User $user, ReportDefinition $definition): bool
    {
        return $user->hasSchoolPermission('reports.definitions.manage', $definition->school_id);
    }
}
