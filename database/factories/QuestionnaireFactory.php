<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questionnaire>
 */
final class QuestionnaireFactory extends Factory
{
    protected $model = Questionnaire::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'owner_user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
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
