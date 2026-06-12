<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Support\Str;

final readonly class PlatformReportingOverviewService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
        private PlatformSupportRedactionService $redaction,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(User $actor, array $filters): array
    {
        $this->authorization->authorizePlatformReportingOverview($actor);

        $baseQuery = ReportRun::query();

        if (isset($filters['from'])) {
            $baseQuery->whereDate('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $baseQuery->whereDate('created_at', '<=', $filters['to']);
        }

        if (isset($filters['school_status'])) {
            $baseQuery->whereHas('school', fn ($query) => $query->where('status', $filters['school_status']));
        }

        if (($filters['report_source'] ?? null) === 'built_in') {
            $baseQuery->whereNull('report_definition_id');
        }

        if (($filters['report_source'] ?? null) === 'custom') {
            $baseQuery->whereNotNull('report_definition_id');
        }

        $data = [
            'reporting_health' => $this->groupedCounts(clone $baseQuery, 'generation_status', ['requested', 'generated', 'failed', 'canceled']),
            'lifecycle_states' => $this->groupedCounts(clone $baseQuery, 'status', ['requested', 'generated', 'failed', 'canceled', 'deleted']),
            'output_availability' => [
                'available' => $this->redaction->protectedCount((clone $baseQuery)->where('outputs_available', true)->count()),
                'unavailable' => $this->redaction->protectedCount((clone $baseQuery)->where('outputs_available', false)->count()),
            ],
            'retention_summary' => [
                'expired_outputs' => $this->redaction->protectedCount((clone $baseQuery)->whereNotNull('output_expires_at')->where('output_expires_at', '<', now())->count()),
                'active_outputs' => $this->redaction->protectedCount((clone $baseQuery)->whereNotNull('output_expires_at')->where('output_expires_at', '>=', now())->count()),
            ],
            'failure_summary' => $this->groupedCounts(clone $baseQuery, 'failure_reason_code', ['source_unavailable', 'generation_failed', 'canceled']),
        ];

        $this->audit->record(
            actor: $actor,
            action: 'platform_reporting_overview_access',
            outcome: 'allowed',
            reasonCode: 'platform_reporting_overview',
            correlationId: (string) Str::uuid(),
            metadata: ['filters_applied' => count($filters)],
        );

        return $data;
    }

    /**
     * @param  array<int, string>  $knownKeys
     * @return array<string, array{value: int|null, suppressed: bool}>
     */
    private function groupedCounts(mixed $query, string $column, array $knownKeys): array
    {
        $counts = $query
            ->selectRaw($column.', count(*) as total')
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('total', $column);

        $result = [];

        foreach ($knownKeys as $key) {
            $result[$key] = $this->redaction->protectedCount((int) ($counts[$key] ?? 0));
        }

        return $result;
    }
}
