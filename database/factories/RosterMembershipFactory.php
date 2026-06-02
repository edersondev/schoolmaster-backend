<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RosterMembership> */
final class RosterMembershipFactory extends Factory
{
    protected $model = RosterMembership::class;

    public function definition(): array
    {
        $classSection = ClassSection::factory()->create();
        $school = $classSection->school;
        $actor = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'registration_number' => strtoupper(fake()->bothify('STU-###')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => 'active',
            'enrolled_at' => '2026-01-01',
        ]);

        return [
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $classSection->academic_period_id,
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $actor->id,
        ];
    }

    public function forClassSection(School $school, ClassSection $classSection, StudentProfile $student, User $actor): self
    {
        return $this->state(fn (): array => [
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $classSection->academic_period_id,
            'created_by_user_id' => $actor->id,
        ]);
    }

    public function ended(User $actor): self
    {
        return $this->state(fn (): array => [
            'status' => 'ended',
            'effective_end_date' => '2026-05-30',
            'end_reason' => 'Student moved roster',
            'ended_by_user_id' => $actor->id,
        ]);
    }
}
