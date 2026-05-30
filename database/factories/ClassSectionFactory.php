<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClassSection> */
final class ClassSectionFactory extends Factory
{
    protected $model = ClassSection::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
        ]);
        $actor = User::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'code' => strtoupper(fake()->bothify('CLS-###')),
            'name' => fake()->words(3, true),
            'course_metadata' => ['code' => 'COURSE', 'name' => 'Course'],
            'classroom_metadata' => ['code' => 'ROOM', 'name' => 'Room'],
            'section_metadata' => ['code' => 'SEC', 'name' => 'Section'],
            'group_metadata' => ['code' => 'GRP', 'name' => 'Group'],
            'status' => 'active',
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ];
    }

    public function forSchoolPeriod(School $school, AcademicPeriod $period, User $actor): self
    {
        return $this->state(fn (): array => [
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
            'inactive_reason' => 'No longer used',
            'inactive_effective_at' => now()->toDateString(),
        ]);
    }
}
