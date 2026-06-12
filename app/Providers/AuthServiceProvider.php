<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\InternalPlatformApproval;
use App\Models\PlatformSupportAuditEvent;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Policies\PlatformSupportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SupportAccessDecision::class, PlatformSupportPolicy::class);
        Gate::policy(TargetSchoolSupportOptIn::class, PlatformSupportPolicy::class);
        Gate::policy(InternalPlatformApproval::class, PlatformSupportPolicy::class);
        Gate::policy(PlatformSupportAuditEvent::class, PlatformSupportPolicy::class);
    }
}
