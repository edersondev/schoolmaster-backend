<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAdminResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_endpoints_return_contract_pagination_metadata(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        foreach (['permissions', 'roles', 'users', 'academic-years', 'academic-periods', 'guardians'] as $path) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson('/api/v1/'.$path)
                ->assertOk()
                ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);
        }
    }
}
