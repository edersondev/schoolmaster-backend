<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\School;
use App\Services\LearningSets\LearningSetEntryValidator;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class LearningSetEntryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_clean_same_school_content(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);

        $this->expectException(ValidationException::class);

        (new LearningSetEntryValidator)->validate([
            ['entry_type' => 'content_item', 'entry_reference_id' => $content->uuid, 'sequence' => 1],
        ], $school->id);
    }
}
