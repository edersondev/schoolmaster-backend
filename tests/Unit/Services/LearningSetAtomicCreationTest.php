<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\LearningSets\CreateLearningSetData;
use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\LearningSets\LearningSetAssignmentValidator;
use App\Services\LearningSets\LearningSetEntryValidator;
use App\Services\LearningSets\LearningSetService;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class LearningSetAtomicCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_student_prevents_partial_learning_set_creation(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $inactiveStudentUser = User::factory()->create(['school_id' => $school->id]);
        $inactiveStudent = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $inactiveStudentUser->id,
            'status' => 'inactive',
        ]);
        $service = new LearningSetService(
            new TenantContextService,
            new TeacherWorkflowListQuery,
            new LearningSetEntryValidator,
            new LearningSetAssignmentValidator,
        );

        $this->expectException(ValidationException::class);

        try {
            $service->create($teacher, new TenantContext($school, 'test', 'resolved'), new CreateLearningSetData(
                academicPeriodId: $period->uuid,
                title: 'Atomic',
                entries: [['entry_type' => 'content_item', 'entry_reference_id' => $content->uuid, 'sequence' => 1]],
                studentProfileIds: [$inactiveStudent->uuid],
            ));
        } finally {
            $this->assertDatabaseCount('learning_sets', 0);
        }
    }
}
