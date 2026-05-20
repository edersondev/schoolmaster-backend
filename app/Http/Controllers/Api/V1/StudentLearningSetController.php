<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Student\StudentLearningSetTimelineResource;
use App\Services\StudentSelfView\StudentLearningTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentLearningSetController extends Controller
{
    public function __construct(private readonly StudentLearningTimelineService $timeline) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->timeline->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, StudentLearningSetTimelineResource::collection($paginator->items())->resolve());
    }
}
