<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\User;

final class ClassSectionPolicy
{
    public function view(User $user, ClassSection $classSection): bool
    {
        return $user->status === 'active'
            && $user->hasSchoolPermission(ClassroomRosterPolicy::MANAGE_PERMISSION, $classSection->school_id);
    }

    public function manage(User $user, ClassSection $classSection): bool
    {
        return $this->view($user, $classSection);
    }
}
