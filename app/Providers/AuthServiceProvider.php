<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentGradingOutcome;
use App\Models\AssessmentResponseAttempt;
use App\Models\InternalPlatformApproval;
use App\Models\PlatformSupportAuditEvent;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Policies\AssessmentPolicy;
use App\Policies\PlatformSupportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AssessmentResponseAttempt::class, AssessmentPolicy::class);
        Gate::policy(AssessmentAnswer::class, AssessmentPolicy::class);
        Gate::policy(AssessmentFileAttachment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentGradingOutcome::class, AssessmentPolicy::class);
        Gate::policy(SupportAccessDecision::class, PlatformSupportPolicy::class);
        Gate::policy(TargetSchoolSupportOptIn::class, PlatformSupportPolicy::class);
        Gate::policy(InternalPlatformApproval::class, PlatformSupportPolicy::class);
        Gate::policy(PlatformSupportAuditEvent::class, PlatformSupportPolicy::class);
    }
}
