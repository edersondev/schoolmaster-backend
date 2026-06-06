<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
final class ReportDefinitionFactory extends Factory
{
    protected $model = ReportDefinition::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'domain' => 'attendance',
            'fields' => ['student_name', 'attendance_status'],
            'filters' => [['field' => 'academic_period_id', 'operator' => 'equals']],
            'grouping' => [],
            'sorting' => [['field' => 'student_name', 'direction' => 'asc']],
            'output_formats' => ['pdf', 'csv'],
            'lifecycle_state' => 'draft',
            'version' => 1,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => ['lifecycle_state' => 'active']);
    }
}
