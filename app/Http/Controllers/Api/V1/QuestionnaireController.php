<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Questionnaires\CreateQuestionnaireData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\CreateQuestionnaireRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\QuestionnaireResource;
use App\Services\Questionnaires\QuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuestionnaireController extends Controller
{
    public function __construct(private readonly QuestionnaireService $questionnaires) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->questionnaires->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, QuestionnaireResource::collection($paginator->items())->resolve());
    }

    public function store(CreateQuestionnaireRequest $request): JsonResponse
    {
        $questionnaire = $this->questionnaires->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateQuestionnaireData::fromArray($request->validated()),
        );

        return ApiResponse::success((new QuestionnaireResource($questionnaire))->resolve(), status: 201);
    }
}
