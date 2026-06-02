<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\School;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeacherAssignment> */
final class TeacherAssignmentFactory extends Factory
{
    protected $model = TeacherAssignment::class;

    public function definition(): array
    {
        $classSection = ClassSection::factory()->create();
        $school = $classSection->school;
        $actor = User::factory()->create(['school_id' => $school->id]);
        $teacher = User::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $classSection->academic_period_id,
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ];
    }

    public function forClassSection(School $school, ClassSection $classSection, User $teacher, User $actor): self
    {
        return $this->state(fn (): array => [
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $classSection->academic_period_id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }

    public function inactive(User $actor): self
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
            'effective_end_date' => '2026-05-30',
            'deactivation_reason' => 'Assignment ended',
            'updated_by_user_id' => $actor->id,
        ]);
    }
}
