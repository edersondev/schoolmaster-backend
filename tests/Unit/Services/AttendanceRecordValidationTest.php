<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TeacherWorkflows\AcademicRecordTargetValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AttendanceRecordValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_inactive_student(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'status' => 'inactive',
        ]);

        $this->expectException(ValidationException::class);

        (new AcademicRecordTargetValidator)->validate($student->uuid, $period->uuid, $school->id);
    }
}
