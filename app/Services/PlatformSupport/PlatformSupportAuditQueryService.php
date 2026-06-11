<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class PlatformSupportAuditQueryService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, PlatformSupportAuditEvent>
     */
    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorization->authorizeSupportAudit($actor);

        $query = PlatformSupportAuditEvent::query()
            ->with(['actor', 'school'])
            ->latest('occurred_at');

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['outcome'])) {
            $query->where('outcome', $filters['outcome']);
        }

        if (isset($filters['school_id'])) {
            $schoolId = School::query()->where('uuid', $filters['school_id'])->value('id');
            $query->where('school_id', $schoolId ?: 0);
        }

        if (isset($filters['correlation_id'])) {
            $query->where('correlation_id', $filters['correlation_id']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }
}
