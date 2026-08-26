<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Resources\ApiResponse;
use Tests\TestCase;

final class ApiResponseTest extends TestCase
{
    public function test_success_and_error_envelopes_match_contract_shape(): void
    {
        $success = ApiResponse::success(['id' => '123']);
        $error = ApiResponse::forbidden();

        $this->assertSame(['id' => '123'], $success->getData(true)['data']);
        $this->assertArrayHasKey('meta', $success->getData(true));
        $this->assertSame('forbidden', $error->getData(true)['error']['code']);
        $this->assertArrayHasKey('details', $error->getData(true)['error']);
    }

    public function test_recoverable_user_conflict_matches_minimal_contract_shape(): void
    {
        $response = ApiResponse::recoverableUserConflict('00000000-0000-4000-8000-000000000001');

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([
            'error' => [
                'code' => 'recoverable_user_conflict',
                'message' => 'A retained user can be restored.',
                'details' => [
                    'user_id' => '00000000-0000-4000-8000-000000000001',
                    'recommended_action' => 'restore',
                ],
            ],
        ], $response->getData(true));
    }
}
