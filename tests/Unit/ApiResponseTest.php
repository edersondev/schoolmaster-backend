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
}
