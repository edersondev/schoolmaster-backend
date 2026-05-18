<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\LearningSets\LearningSetAssignmentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class LearningSetAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_active_same_school_student_profiles(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $studentUser = User::factory()->create(['school_id' => $otherSchool->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $otherSchool->id,
            'user_id' => $studentUser->id,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        (new LearningSetAssignmentValidator)->validate([$student->uuid], $school->id);
    }
}
