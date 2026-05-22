<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EnrollmentHistory;
use App\Models\School;
use App\Models\StudentTransfer;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_record_source_school_transfer(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_transfers.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles/'.$profile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'Moved to another school.',
            ])
            ->assertOk()
            ->assertJsonPath('data.student_profile.status', 'transferred')
            ->assertJsonPath('data.enrollment_history.event_type', 'transferred_out');

        $this->assertSame(1, StudentTransfer::query()->count());
        $this->assertSame(1, EnrollmentHistory::query()->where('event_type', 'transferred_out')->count());
    }
}
