<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\LearningSet;
use App\Models\LearningSetAssignment;

final class GetGuardianStudentAcademicsTest extends GuardianSelfServiceTestCase
{
    public function test_guardian_academic_summary_exposes_only_approved_summary_fields(): void
    {
        [$school, , , $guardianUser, $student, $period] = $this->guardianContext();
        $teacher = $this->createTeacher($school);

        GradeRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'grade_value' => 80,
            'grade_label' => 'B',
            'status' => 'active',
        ]);
        AttendanceRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'attendance_date' => '2026-01-10',
            'attendance_status' => 'absent',
            'status' => 'active',
        ]);
        $learningSet = LearningSet::query()->create([
            'school_id' => $school->id,
            'owner_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'title' => 'Fractions',
            'status' => 'published',
        ]);
        LearningSetAssignment::query()->create([
            'school_id' => $school->id,
            'learning_set_id' => $learningSet->id,
            'assignment_mode' => 'student',
            'student_profile_id' => $student->id,
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}/academics?academic_period_id={$period->uuid}")
            ->assertOk()
            ->assertJsonPath('data.grade_summary.average', 80)
            ->assertJsonPath('data.attendance_summary.total_absences', 1)
            ->assertJsonPath('data.learning_sets.0.title', 'Fractions')
            ->assertJsonMissing(['correction_history'])
            ->assertJsonMissing(['recorded_by_user_id']);
    }
}
