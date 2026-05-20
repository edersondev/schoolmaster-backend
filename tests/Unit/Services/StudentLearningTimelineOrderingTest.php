<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentSelfView\StudentLearningTimelineService;
use App\Services\StudentSelfView\StudentSelfViewListQuery;
use App\Services\TenantContextService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentLearningTimelineOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_sets_are_ordered_by_publish_date_descending(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $older = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $older->update(['published_at' => now()->subDay()]);
        $newer = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $newer->update(['published_at' => now()]);

        $service = new StudentLearningTimelineService(new TenantContextService, new StudentSelfViewListQuery);
        $paginator = $service->list($studentUser, new TenantContext($school, 'test', 'resolved'), ['academic_period_id' => $period->uuid]);

        $this->assertSame($newer->uuid, $paginator->items()[0]->uuid);
        $this->assertSame($older->uuid, $paginator->items()[1]->uuid);
    }
}
