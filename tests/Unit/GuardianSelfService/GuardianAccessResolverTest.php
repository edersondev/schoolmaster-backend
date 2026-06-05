<?php

declare(strict_types=1);

namespace Tests\Unit\GuardianSelfService;

use App\DTOs\TenantContext;
use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\GuardianSelfService\GuardianAccessResolver;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_requires_active_link_and_active_association(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian', 'relationship_type' => 'guardian', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'active']);
        $resolver = new GuardianAccessResolver(new TenantContextService);

        try {
            $resolver->resolveActor($user, new TenantContext($school, 'test', 'resolved'));
            $this->fail('Expected missing guardian-user link to deny access.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        GuardianUserLink::query()->create(['school_id' => $school->id, 'guardian_id' => $guardian->id, 'user_id' => $user->id, 'status' => 'active']);
        $actor = $resolver->resolveActor($user, new TenantContext($school, 'test', 'resolved'));

        $this->expectException(ModelNotFoundException::class);
        $resolver->resolveTarget($actor, $student->uuid);
    }
}
