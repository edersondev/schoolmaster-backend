<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class PlatformSupportAuthorizationService
{
    public function __construct(
        private readonly PlatformSupportAuditService $audit,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function authorizePlatformSchoolSummaries(User $actor): void
    {
        $this->authorize($actor, 'viewSchoolSummaries', PlatformSupportAuditEvent::class, 'platform_school_summary_access', 'platform_overview_forbidden');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizePlatformReportingOverview(User $actor): void
    {
        $this->authorize($actor, 'viewReportingOverview', PlatformSupportAuditEvent::class, 'platform_reporting_overview_access', 'platform_reporting_forbidden');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeSupportAccessRequest(User $actor): void
    {
        $this->authorize($actor, 'requestSupportAccess', SupportAccessDecision::class, 'support_access_requested', 'support_drill_down_forbidden');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeSupportApproval(User $actor): void
    {
        $this->authorize($actor, 'approveSupportAccess', SupportAccessDecision::class, 'support_access_approved', 'support_approval_forbidden');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeSupportOptIn(User $actor, School $school): void
    {
        $this->authorize($actor, 'createSchoolSupportOptIn', [TargetSchoolSupportOptIn::class, $school], 'support_opt_in_created', 'support_opt_in_forbidden', $school);
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeSupportAudit(User $actor): void
    {
        $this->authorize($actor, 'viewAuditEvents', PlatformSupportAuditEvent::class, 'support_audit_review', 'support_audit_forbidden');
    }

    /**
     * @param  class-string|array<int, mixed>  $arguments
     *
     * @throws AuthorizationException
     */
    private function authorize(User $actor, string $ability, string|array $arguments, string $action, string $reasonCode, ?School $school = null): void
    {
        if (Gate::forUser($actor)->allows($ability, $arguments)) {
            return;
        }

        $this->audit->record(
            actor: $actor,
            action: 'denied_access',
            outcome: 'denied',
            reasonCode: $reasonCode,
            correlationId: 'authorization-denied',
            school: $school,
            metadata: ['operation' => $action],
        );

        throw new AuthorizationException('The authenticated user lacks permission for this action.');
    }
}
