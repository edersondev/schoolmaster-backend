<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningSet;
use App\Models\LearningSetAssignment;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningSetAssignment>
 */
final class LearningSetAssignmentFactory extends Factory
{
    protected $model = LearningSetAssignment::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => User::factory()->create(['school_id' => $school->id])->id,
            'status' => 'active',
        ]);

        return [
            'school_id' => $school->id,
            'learning_set_id' => LearningSet::factory(),
            'assignment_mode' => 'legacy_direct',
            'student_profile_id' => $student->id,
            'status' => 'active',
            'assigned_at' => now(),
        ];
    }
}
