<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\EnrollmentHistory;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\AdministrationLifecyclePolicy;
use App\Policies\ClassSectionPolicy;
use App\Policies\EnrollmentHistoryPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\StudentTransferPolicy;
use App\Policies\TeacherAssignmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(School::class, AdministrationLifecyclePolicy::class);
        Gate::policy(User::class, AdministrationLifecyclePolicy::class);
        Gate::policy(Role::class, AdministrationLifecyclePolicy::class);
        Gate::policy(AcademicYear::class, AdministrationLifecyclePolicy::class);
        Gate::policy(AcademicPeriod::class, AdministrationLifecyclePolicy::class);
        Gate::policy(ClassSection::class, ClassSectionPolicy::class);
        Gate::policy(TeacherAssignment::class, TeacherAssignmentPolicy::class);
        Gate::policy(Guardian::class, AdministrationLifecyclePolicy::class);
        Gate::policy(StudentProfile::class, StudentProfilePolicy::class);
        Gate::policy(EnrollmentHistory::class, EnrollmentHistoryPolicy::class);
        Gate::policy(StudentTransfer::class, StudentTransferPolicy::class);
    }
}
