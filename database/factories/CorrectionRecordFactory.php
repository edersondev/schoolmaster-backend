<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CorrectionRecord;
use App\Models\GradeRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CorrectionRecord> */
final class CorrectionRecordFactory extends Factory
{
    protected $model = CorrectionRecord::class;

    public function definition(): array
    {
        $grade = GradeRecord::factory()->create();

        return [
            'school_id' => $grade->school_id,
            'target_record_type' => 'grade',
            'target_record_id' => $grade->uuid,
            'original_value' => ['grade_value' => 80],
            'new_value' => ['grade_value' => 85],
            'correction_reason' => 'Corrected after manual review.',
            'actor_user_id' => $grade->recorded_by_user_id,
            'academic_period_id' => $grade->academic_period_id,
            'student_profile_id' => $grade->student_profile_id,
            'student_visible' => true,
            'corrected_at' => now(),
        ];
    }
}
