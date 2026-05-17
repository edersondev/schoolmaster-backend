<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\TenantContextException;
use App\Models\School;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class TenantContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_session_bound_school_context(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $context = app(TenantContextResolver::class)->resolve(Request::create('/'), $user->load('school'));

        $this->assertTrue($context->isResolved());
        $this->assertSame($school->id, $context->school->id);
    }

    public function test_rejects_mismatched_school_context(): void
    {
        $this->expectException(TenantContextException::class);

        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $request = Request::create('/', server: ['HTTP_X_SCHOOL_ID' => $otherSchool->uuid]);

        app(TenantContextResolver::class)->resolve($request, $user->load('school'));
    }
}
