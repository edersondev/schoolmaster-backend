<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherWorkflow\TeacherMaterialsRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TeacherWorkflow\TeacherMaterialsResource;
use App\Services\TeacherWorkflow\QuestionnaireService;
use App\Services\TeacherWorkflow\TeacherContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeacherMaterialsController extends Controller
{
    public function __construct(
        private readonly TeacherContentService $content,
        private readonly QuestionnaireService $questionnaires,
    ) {}

    public function showContent(Request $request, string $contentItemId): JsonResponse
    {
        $content = $this->content->get($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId);

        return ApiResponse::success((new TeacherMaterialsResource($content))->resolve());
    }

    public function updateContent(TeacherMaterialsRequest $request, string $contentItemId): JsonResponse
    {
        $content = $this->content->update($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId, $request->validated());

        return ApiResponse::success((new TeacherMaterialsResource($content))->resolve());
    }

    public function updateContentStatus(TeacherMaterialsRequest $request, string $contentItemId): JsonResponse
    {
        $content = $this->content->status($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId, $request->validated('status'));

        return ApiResponse::success((new TeacherMaterialsResource($content))->resolve());
    }

    public function deleteContent(Request $request, string $contentItemId): JsonResponse
    {
        $content = $this->content->delete($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId);

        return ApiResponse::success((new TeacherMaterialsResource($content))->resolve());
    }

    public function restoreContent(TeacherMaterialsRequest $request, string $contentItemId): JsonResponse
    {
        $content = $this->content->restore($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId);

        return ApiResponse::success((new TeacherMaterialsResource($content))->resolve());
    }

    public function downloadContent(Request $request, string $contentItemId): JsonResponse
    {
        $result = $this->content->download($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $contentItemId);

        return ApiResponse::success(TeacherMaterialsResource::download($result['content'], $result['download_url'], $result['expires_at']));
    }

    public function showQuestionnaire(Request $request, string $questionnaireId): JsonResponse
    {
        $questionnaire = $this->questionnaires->get($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $questionnaireId);

        return ApiResponse::success((new TeacherMaterialsResource($questionnaire))->resolve());
    }

    public function updateQuestionnaire(TeacherMaterialsRequest $request, string $questionnaireId): JsonResponse
    {
        $questionnaire = $this->questionnaires->update($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $questionnaireId, $request->validated());

        return ApiResponse::success((new TeacherMaterialsResource($questionnaire))->resolve());
    }

    public function updateQuestionnaireStatus(TeacherMaterialsRequest $request, string $questionnaireId): JsonResponse
    {
        $questionnaire = $this->questionnaires->status($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $questionnaireId, $request->validated('status'));

        return ApiResponse::success((new TeacherMaterialsResource($questionnaire))->resolve());
    }

    public function deleteQuestionnaire(Request $request, string $questionnaireId): JsonResponse
    {
        $questionnaire = $this->questionnaires->delete($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $questionnaireId);

        return ApiResponse::success((new TeacherMaterialsResource($questionnaire))->resolve());
    }

    public function restoreQuestionnaire(TeacherMaterialsRequest $request, string $questionnaireId): JsonResponse
    {
        $questionnaire = $this->questionnaires->restore($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $questionnaireId);

        return ApiResponse::success((new TeacherMaterialsResource($questionnaire))->resolve());
    }
}
