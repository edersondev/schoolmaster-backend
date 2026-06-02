<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningSet;
use App\Models\LearningSetEntry;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningSetEntry>
 */
final class LearningSetEntryFactory extends Factory
{
    protected $model = LearningSetEntry::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'learning_set_id' => LearningSet::factory(),
            'entry_type' => 'content_item',
            'entry_reference_id' => 1,
            'sequence' => 1,
        ];
    }
}
