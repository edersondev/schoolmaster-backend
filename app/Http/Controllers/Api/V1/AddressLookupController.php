<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddressLookup\LookupAddressRequest;
use App\Http\Resources\ApiResponse;
use App\Services\AddressLookup\AddressLookupNotFoundException;
use App\Services\AddressLookup\AddressLookupUnavailableException;
use App\Services\AddressLookup\ViaCepAddressLookupService;
use Illuminate\Http\JsonResponse;

final class AddressLookupController extends Controller
{
    public function __invoke(LookupAddressRequest $request, ViaCepAddressLookupService $lookup): JsonResponse
    {
        try {
            return ApiResponse::success($lookup->lookup((string) $request->validated('zip_code')));
        } catch (AddressLookupNotFoundException) {
            return ApiResponse::notFound('Address was not found for the provided zip code.');
        } catch (AddressLookupUnavailableException $exception) {
            return ApiResponse::error(
                'temporary_unavailable',
                $exception->getMessage() ?: 'ViaCEP lookup is temporarily unavailable.',
                [],
                503,
            );
        }
    }
}
