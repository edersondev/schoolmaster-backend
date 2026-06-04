<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuardianUserLink>
 */
final class GuardianUserLinkFactory extends Factory
{
    protected $model = GuardianUserLink::class;

    public function definition(): array
    {
        $school = School::factory()->create();

        return [
            'school_id' => $school->id,
            'guardian_id' => Guardian::query()->create([
                'school_id' => $school->id,
                'full_name' => fake()->name(),
                'relationship_type' => 'guardian',
                'contact_email' => fake()->safeEmail(),
                'contact_phone' => fake()->phoneNumber(),
                'status' => 'active',
            ])->id,
            'user_id' => User::factory()->create(['school_id' => $school->id])->id,
            'created_by_user_id' => User::factory()->create(['school_id' => $school->id])->id,
            'status' => 'active',
        ];
    }
}
