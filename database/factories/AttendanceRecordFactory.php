<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceRecord> */
final class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        [$school, $teacher, $period, $student] = $this->context();

        return [
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'original_recorded_by_user_id' => $teacher->id,
            'attendance_date' => '2026-02-01',
            'attendance_status' => 'present',
            'status' => 'active',
        ];
    }

    public function inactive(): self
    {
        return $this->state(['status' => 'inactive']);
    }

    public function deleted(): self
    {
        return $this->state(['status' => 'deleted', 'deleted_at' => now()]);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $period, $student];
    }
}
