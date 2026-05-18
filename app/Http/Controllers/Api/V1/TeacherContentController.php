<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TeacherContent\CreateTeacherContentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherContent\CreateTeacherContentRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TeacherContentResource;
use App\Services\TeacherContent\TeacherContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeacherContentController extends Controller
{
    public function __construct(private readonly TeacherContentService $content) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->content->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, TeacherContentResource::collection($paginator->items())->resolve());
    }

    public function store(CreateTeacherContentRequest $request): JsonResponse
    {
        $content = $this->content->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateTeacherContentData::fromArray($request->validated()),
        );

        return ApiResponse::success((new TeacherContentResource($content))->resolve(), status: 201);
    }
}
