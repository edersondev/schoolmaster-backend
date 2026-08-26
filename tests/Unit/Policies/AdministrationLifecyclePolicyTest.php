<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\School;
use App\Models\User;
use App\Policies\AdministrationLifecyclePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecyclePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_disclosure_requires_deleted_exact_school_target_and_both_permissions(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $target = User::factory()->create(['school_id' => $school->id]);
        $target->delete();
        $policy = app(AdministrationLifecyclePolicy::class);

        $authorized = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $missingView = $this->createSchoolAdmin($school, ['users.manage']);
        $missingManage = $this->createSchoolAdmin($school, ['users.view']);
        $otherSchoolActor = $this->createSchoolAdmin($otherSchool, ['users.view', 'users.manage']);

        $this->assertTrue($policy->canDiscloseDuplicateEmailRecovery($authorized, $school, $target));
        $this->assertFalse($policy->canDiscloseDuplicateEmailRecovery($missingView, $school, $target));
        $this->assertFalse($policy->canDiscloseDuplicateEmailRecovery($missingManage, $school, $target));
        $this->assertFalse($policy->canDiscloseDuplicateEmailRecovery($otherSchoolActor, $otherSchool, $target));
        $this->assertFalse($policy->canDiscloseDuplicateEmailRecovery($this->createPlatformUser(['schools.manage']), $school, $target));

        $target->restore();
        $this->assertFalse($policy->canDiscloseDuplicateEmailRecovery($authorized, $school, $target));
    }
}
