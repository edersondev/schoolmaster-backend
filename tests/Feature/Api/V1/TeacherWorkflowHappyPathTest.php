<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TeacherWorkflowHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_workflow_from_content_to_records_does_not_leak_cross_tenant_data(): void
    {
        Storage::fake('teacher_content');
        [$school, $teacher, $period, $student] = $this->context();
        $token = $this->bearerTokenFor($teacher);

        $content = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->post('/api/v1/teacher-content', [
                'title' => 'Lesson',
                'content_type' => 'pdf',
                'file' => UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
            ])
            ->assertCreated()
            ->json('data');

        TeacherContentItem::query()->where('uuid', $content['id'])->update(['scan_status' => 'clean']);

        $questionnaire = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/questionnaires', [
                'title' => 'Quiz',
                'questions' => [
                    ['question_type' => 'true_false', 'prompt' => 'Ready?', 'sequence' => 1],
                ],
            ])
            ->assertCreated()
            ->json('data');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/learning-sets', [
                'academic_period_id' => $period->uuid,
                'title' => 'Set',
                'entries' => [
                    ['entry_type' => 'content_item', 'entry_reference_id' => $content['id'], 'sequence' => 1],
                    ['entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire['id'], 'sequence' => 2],
                ],
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertCreated();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/grades', [
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 92,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/attendance', [
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'attendance_date' => '2026-02-01',
                'attendance_status' => 'present',
            ])
            ->assertCreated();
    }

    private function context(): array
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
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'status' => 'active',
            'current_academic_year_id' => $year->id,
        ]);

        return [$school, $teacher, $period, $student];
    }
}
