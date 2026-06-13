<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\TenantContext;
use App\Exceptions\PermissionDeniedException;
use App\Models\AssessmentResponseAttempt;
use App\Models\User;
use App\Repositories\AssessmentQueryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class AssessmentResponseReviewService
{
    public function __construct(
        private readonly AssessmentTenantScopeService $tenantScope,
        private readonly AssessmentReviewAuthorizationService $authorization,
        private readonly AssessmentAuditService $audit,
        private readonly AssessmentQueryRepository $repository,
    ) {}

    public function list(User $actor, TenantContext $tenantContext, array $filters): LengthAwarePaginator
    {
        $context = $this->tenantScope->actorContext($actor, $tenantContext);

        if (
            $actor->school_id !== $context->school->id
            || (! $actor->hasSchoolPermission('users.manage', $context->school->id)
                && ! $actor->hasSchoolPermission('questionnaires.manage', $context->school->id))
        ) {
            throw new PermissionDeniedException('The authenticated user lacks same-school assessment review authority.');
        }

        $paginator = $this->repository->paginateAttempts($context->school->id, $filters);

        foreach ($paginator->items() as $attempt) {
            $this->authorization->assertCanReview($actor, $attempt->loadMissing('questionnaire'));
        }

        $this->audit->record($context, 'review', 'succeeded', 'responses_listed', null, [
            'result_count' => count($paginator->items()),
        ]);

        return $paginator;
    }

    public function get(User $actor, TenantContext $tenantContext, string $attemptUuid): AssessmentResponseAttempt
    {
        $context = $this->tenantScope->actorContext($actor, $tenantContext);
        $attempt = $this->repository->findAttempt($attemptUuid, $context->school->id);

        if ($attempt === null) {
            throw (new ModelNotFoundException)->setModel(AssessmentResponseAttempt::class, [$attemptUuid]);
        }

        $this->authorization->assertCanReview($actor, $attempt);
        $this->audit->record($context, 'review', 'succeeded', 'response_viewed', $attempt);

        return $attempt;
    }
}
