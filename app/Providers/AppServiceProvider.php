<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\EnrollmentHistory;
use App\Models\GradeRecord;
use App\Models\Guardian;
use App\Models\ImportRun;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\TeacherAssignment;
use App\Models\TeacherContentItem;
use App\Models\User;
use App\Policies\AdministrationLifecyclePolicy;
use App\Policies\AcademicRecordPolicy;
use App\Policies\AcademicRecordImportPolicy;
use App\Policies\ClassSectionPolicy;
use App\Policies\EnrollmentHistoryPolicy;
use App\Policies\LearningSetPolicy;
use App\Policies\QuestionnairePolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\StudentTransferPolicy;
use App\Policies\TeacherAssignmentPolicy;
use App\Policies\TeacherContentItemPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Repositories\TeacherWorkflow\TeacherWorkflowLookupRepository::class);
        $this->app->singleton(\App\Services\TeacherWorkflow\SchoolContextGuard::class);
        $this->app->singleton(\App\Services\TeacherWorkflow\TeacherWorkflowAuditLogger::class);
        $this->app->singleton(\App\Services\TeacherWorkflow\LifecycleTransitionService::class);
        $this->app->singleton(\App\Services\TeacherWorkflow\StudentVisibilityProjector::class);
        $this->app->singleton(\App\Services\TeacherWorkflow\HistoricalMeaningGuard::class);
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
        Gate::policy(TeacherContentItem::class, TeacherContentItemPolicy::class);
        Gate::policy(Questionnaire::class, QuestionnairePolicy::class);
        Gate::policy(LearningSet::class, LearningSetPolicy::class);
        Gate::policy(GradeRecord::class, AcademicRecordPolicy::class);
        Gate::policy(AttendanceRecord::class, AcademicRecordPolicy::class);
        Gate::policy(ImportRun::class, AcademicRecordImportPolicy::class);
    }
}
