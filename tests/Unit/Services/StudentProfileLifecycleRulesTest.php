<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\User;
use App\Services\StudentProfiles\StudentProfileLifecycleRules;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileLifecycleRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_rules_reject_transfer_status_on_status_endpoint(): void
    {
        $school = School::factory()->create();
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->expectException(ConflictException::class);

        app(StudentProfileLifecycleRules::class)->assertNonTransferTransition($profile, 'transferred');
    }
}
