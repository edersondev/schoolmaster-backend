<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Guardian;

use App\DTOs\GuardianSelfService\GuardianAcademicSummaryQuery;
use App\DTOs\GuardianSelfService\GuardianActorContext;
use App\DTOs\GuardianSelfService\GuardianContactViewQuery;
use App\DTOs\GuardianSelfService\GuardianStudentTarget;
use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guardian\GetGuardianStudentAcademicsRequest;
use App\Http\Requests\Api\V1\Guardian\GetGuardianStudentContactsRequest;
use App\Http\Requests\Api\V1\Guardian\GetGuardianStudentRequest;
use App\Http\Requests\Api\V1\Guardian\ListGuardianStudentsRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Guardian\GuardianAcademicSummaryResource;
use App\Http\Resources\Guardian\GuardianStudentContactsResource;
use App\Http\Resources\Guardian\GuardianStudentListResource;
use App\Http\Resources\Guardian\GuardianStudentResource;
use App\Services\GuardianSelfService\GuardianAccessResolver;
use App\Services\GuardianSelfService\GuardianAcademicSummaryService;
use App\Services\GuardianSelfService\GuardianAuditService;
use App\Services\GuardianSelfService\GuardianContactService;
use App\Services\GuardianSelfService\GuardianStudentService;
use App\Services\GuardianSelfService\GuardianVisibilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GuardianSelfServiceController extends Controller
{
    public function __construct(
        private readonly GuardianAccessResolver $access,
        private readonly GuardianStudentService $students,
        private readonly GuardianVisibilityService $visibility,
        private readonly GuardianAcademicSummaryService $academics,
        private readonly GuardianContactService $contacts,
        private readonly GuardianAuditService $audit,
    ) {}

    public function index(ListGuardianStudentsRequest $request): JsonResponse
    {
        $actor = $this->resolveActorFor($request, 'student_list');

        $paginator = $this->students->list($actor, (int) ($request->validated('per_page') ?? 25));
        $data = array_map(
            fn (array $student): array => (new GuardianStudentListResource($student))->resolve(),
            $this->students->summarizePage($paginator),
        );

        $this->audit->allowed($request, $actor, 'student_list', metadata: ['count' => count($data)]);

        return ApiResponse::paginated($paginator, $data);
    }

    public function show(GetGuardianStudentRequest $request, string $studentProfileId): JsonResponse
    {
        $actor = $this->resolveActorFor($request, 'student_detail');
        $target = $this->resolveTargetFor($request, $actor, 'student_detail', $studentProfileId);
        $this->audit->allowed($request, $actor, 'student_detail', $target->student);

        return ApiResponse::success((new GuardianStudentResource($this->visibility->studentDetail($target)))->resolve());
    }

    public function academics(GetGuardianStudentAcademicsRequest $request, string $studentProfileId): JsonResponse
    {
        $actor = $this->resolveActorFor($request, 'academic_summary');
        $target = $this->resolveTargetFor($request, $actor, 'academic_summary', $studentProfileId);
        try {
            $period = $this->academics->resolveAcademicPeriod($request->validated('academic_period_id'), $actor->school->id);
        } catch (ModelNotFoundException $exception) {
            $this->audit->denied($request, 'academic_summary', 'academic_period_not_found', $actor->user, $actor->school, $target->student->uuid);

            throw $exception;
        }

        $summary = $this->academics->summary(new GuardianAcademicSummaryQuery($target, $period));
        $this->audit->allowed($request, $actor, 'academic_summary', $target->student, ['academic_period_id' => $period->uuid]);

        return ApiResponse::success((new GuardianAcademicSummaryResource($summary))->resolve());
    }

    public function contacts(GetGuardianStudentContactsRequest $request, string $studentProfileId): JsonResponse
    {
        $actor = $this->resolveActorFor($request, 'contact_view');
        $target = $this->resolveTargetFor($request, $actor, 'contact_view', $studentProfileId);
        $this->audit->allowed($request, $actor, 'contact_view', $target->student);

        return ApiResponse::success((new GuardianStudentContactsResource($this->contacts->view(new GuardianContactViewQuery($target))))->resolve());
    }

    private function resolveActorFor(Request $request, string $action): GuardianActorContext
    {
        try {
            return $this->access->resolveActor(
                $request->attributes->get('auth_user'),
                $request->attributes->get('tenant_context'),
            );
        } catch (AuthorizationException|TenantContextException $exception) {
            $context = $request->attributes->get('tenant_context');
            $this->audit->denied(
                $request,
                $action,
                $exception instanceof TenantContextException ? 'tenant_context_unresolved' : 'guardian_actor_denied',
                $request->attributes->get('auth_user'),
                $context instanceof TenantContext && $context->isResolved() ? $context->school : null,
            );

            throw $exception;
        }
    }

    private function resolveTargetFor(Request $request, GuardianActorContext $actor, string $action, string $studentProfileId): GuardianStudentTarget
    {
        try {
            return $this->access->resolveTarget($actor, $studentProfileId);
        } catch (ModelNotFoundException $exception) {
            $this->audit->denied($request, $action, 'student_not_visible', $actor->user, $actor->school);

            throw $exception;
        }
    }
}
