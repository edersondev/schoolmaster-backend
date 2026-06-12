<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\ReportRun;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\SupportAccessDecision;
use App\Models\User;

final readonly class SupportDiagnosticService
{
    public function __construct(
        private SupportAccessDecisionService $decisions,
        private PlatformSupportRedactionService $redaction,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function diagnostics(User $actor, string $schoolUuid, array $query): array
    {
        $decision = $this->decisions->authorizeDiagnostics($actor, $schoolUuid, $query['support_access_id']);
        $school = $decision->school;

        $this->audit->record($actor, 'support_drill_down_access', 'allowed', $query['reason_code'], $query['correlation_id'], $school, $decision);

        return [
            'school_id' => $school->uuid,
            'school_status' => $school->status,
            'operational_indicators' => [
                'students' => $this->redaction->protectedCount(StudentProfile::query()->where('school_id', $school->id)->count()),
                'school_users' => $this->redaction->protectedCount(User::query()->where('school_id', $school->id)->count()),
            ],
            'report_health' => $this->reportHealth($school),
            'lifecycle_summary' => [
                'active_support_decisions' => $this->redaction->protectedCount(SupportAccessDecision::query()
                    ->where('school_id', $school->id)
                    ->where('state', 'approved')
                    ->where('expires_at', '>', now())
                    ->count()),
            ],
            'support_metadata' => [
                'diagnostics_scope' => 'read_only_redacted',
                'decision_id' => $decision->uuid,
            ],
            'correlation_id' => $query['correlation_id'],
        ];
    }

    /**
     * @return array<string, array{value: int|null, suppressed: bool}>
     */
    private function reportHealth(School $school): array
    {
        $counts = ReportRun::query()
            ->where('school_id', $school->id)
            ->selectRaw('generation_status, count(*) as total')
            ->groupBy('generation_status')
            ->pluck('total', 'generation_status');

        return [
            'requested' => $this->redaction->protectedCount((int) ($counts['requested'] ?? 0)),
            'generated' => $this->redaction->protectedCount((int) ($counts['generated'] ?? 0)),
            'failed' => $this->redaction->protectedCount((int) ($counts['failed'] ?? 0)),
        ];
    }
}
