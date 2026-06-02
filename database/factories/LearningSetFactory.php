<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSet;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningSet>
 */
final class LearningSetFactory extends Factory
{
    protected $model = LearningSet::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $owner = User::factory()->create(['school_id' => $school->id]);
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

        return [
            'school_id' => $school->id,
            'owner_user_id' => $owner->id,
            'academic_period_id' => $period->id,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
            'published_at' => now(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(['status' => 'inactive']);
    }

    public function deleted(): self
    {
        return $this->state([
            'status' => 'deleted',
            'deleted_at' => now(),
        ]);
    }
}
