<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\SchoolInstitutionalLookup;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SchoolLookupController extends Controller
{
    public function administrativeTypes(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::ADMINISTRATIVE_TYPE);
    }

    public function legalNatures(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::LEGAL_NATURE);
    }

    public function managementTypes(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::MANAGEMENT_TYPE);
    }

    public function pedagogicalApproaches(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::PEDAGOGICAL_APPROACH);
    }

    public function educationLevels(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::EDUCATION_LEVEL);
    }

    public function modalities(Request $request): JsonResponse
    {
        return $this->list($request, SchoolInstitutionalLookup::MODALITY);
    }

    private function list(Request $request, string $group): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->attributes->get('auth_user');

        if (! $actor->hasPermission('schools.view', 'platform') && ! $actor->hasPermission('schools.manage', 'platform')) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }

        $options = SchoolInstitutionalLookup::query()
            ->where('group', $group)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SchoolInstitutionalLookup $option): array => [
                'id' => $option->option_id,
                'label' => $option->label,
                'status' => $option->status,
                'sort_order' => $option->sort_order,
            ])
            ->all();

        return ApiResponse::success($options);
    }
}
