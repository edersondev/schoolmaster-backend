<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListPlatformSchoolSummariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_actor_can_list_minimized_school_summaries_with_suppressed_counts_and_audit(): void
    {
        $school = School::factory()->create(['name' => 'Alpha School']);

        for ($i = 0; $i < 3; $i++) {
            $studentUser = User::factory()->create(['school_id' => $school->id]);
            StudentProfile::query()->create([
                'school_id' => $school->id,
                'user_id' => $studentUser->id,
                'registration_number' => 'STU-'.$i,
                'first_name' => 'Student',
                'last_name' => (string) $i,
                'status' => 'active',
                'enrolled_at' => now()->toDateString(),
                'status_effective_at' => now()->toDateString(),
            ]);
        }

        $actor = $this->createPlatformUser(['platform_support.overview']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/schools?sort=name')
            ->assertOk()
            ->assertJsonPath('data.0.school_id', $school->uuid)
            ->assertJsonPath('data.0.protected_counts.students.value', null)
            ->assertJsonPath('data.0.protected_counts.students.suppressed', true)
            ->assertJsonMissingPath('data.0.students')
            ->assertJsonMissingPath('data.0.guardians')
            ->assertJsonMissingPath('data.0.report_runs');

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'platform_school_summary_access')
            ->where('outcome', 'allowed')
            ->count());
    }

    public function test_platform_school_summary_rejects_unsupported_sort_fields(): void
    {
        $actor = $this->createPlatformUser(['platform_support.overview']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/schools?sort=email')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }
}
