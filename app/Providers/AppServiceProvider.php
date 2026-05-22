<?php

namespace App\Providers;

use App\Models\EnrollmentHistory;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Policies\EnrollmentHistoryPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\StudentTransferPolicy;
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
        Gate::policy(StudentProfile::class, StudentProfilePolicy::class);
        Gate::policy(EnrollmentHistory::class, EnrollmentHistoryPolicy::class);
        Gate::policy(StudentTransfer::class, StudentTransferPolicy::class);
    }
}
