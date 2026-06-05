<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReportLifecycleEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportLifecycleEvent>
 */
final class ReportLifecycleEventFactory extends Factory
{
    protected $model = ReportLifecycleEvent::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'actor_user_id' => $user->id,
            'action' => 'catalog_viewed',
            'outcome' => 'succeeded',
            'target_type' => 'report_catalog',
            'target_id' => null,
            'correlation_id' => fake()->uuid(),
            'reason_code' => 'catalog_viewed',
            'summary' => [],
            'occurred_at' => now(),
        ];
    }
}
