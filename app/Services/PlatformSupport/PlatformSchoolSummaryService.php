<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\Guardian;
use App\Models\PlatformSupportAuditEvent;
use App\Models\ReportRun;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\SupportAccessDecision;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final readonly class PlatformSchoolSummaryService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
        private PlatformSupportRedactionService $redaction,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorization->authorizePlatformSchoolSummaries($actor);

        $query = School::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        foreach ($this->resolveSorts($filters['sort'] ?? 'name') as [$sortField, $sortDirection]) {
            $query->orderBy($sortField, $sortDirection);
        }

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->transform(fn (School $school): array => $this->summaryFor($school));

        $this->audit->record(
            actor: $actor,
            action: 'platform_school_summary_access',
            outcome: 'allowed',
            reasonCode: 'platform_overview',
            correlationId: (string) Str::uuid(),
            metadata: ['result_count' => $paginator->count()],
        );

        return $paginator;
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryFor(School $school): array
    {
        return [
            'school_id' => $school->uuid,
            'name' => $school->name,
            'status' => $school->status,
            'protected_counts' => [
                'students' => $this->redaction->protectedCount(StudentProfile::query()->where('school_id', $school->id)->count()),
                'guardians' => $this->redaction->protectedCount(Guardian::query()->where('school_id', $school->id)->count()),
                'teachers' => $this->redaction->protectedCount(User::query()->where('school_id', $school->id)->count()),
                'report_runs' => $this->redaction->protectedCount(ReportRun::query()->where('school_id', $school->id)->count()),
            ],
            'report_health' => $this->reportHealth($school),
            'lifecycle_summary' => [
                'active_users' => $this->redaction->protectedCount(User::query()->where('school_id', $school->id)->where('status', 'active')->count()),
            ],
            'support_diagnostics' => [
                'active_support_decisions' => SupportAccessDecision::query()
                    ->where('school_id', $school->id)
                    ->where('state', 'approved')
                    ->where('expires_at', '>', now())
                    ->count(),
                'last_support_audit_at' => PlatformSupportAuditEvent::query()
                    ->where('school_id', $school->id)
                    ->latest('occurred_at')
                    ->value('occurred_at'),
            ],
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
            'canceled' => $this->redaction->protectedCount((int) ($counts['canceled'] ?? 0)),
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function resolveSorts(mixed $sort): array
    {
        $sort = is_string($sort) && $sort !== '' ? $sort : 'name';
        $resolved = [];

        foreach (explode(',', $sort) as $fieldSort) {
            $direction = str_starts_with($fieldSort, '-') ? 'desc' : 'asc';
            $field = ltrim($fieldSort, '-');

            if (in_array($field, ['name', 'status', 'created_at'], true)) {
                $resolved[] = [$field, $direction];
            }
        }

        return $resolved === [] ? [['name', 'asc']] : $resolved;
    }
}
