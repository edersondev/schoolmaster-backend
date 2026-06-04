<?php

declare(strict_types=1);

namespace Tests\Unit\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\Exceptions\ConflictException;
use App\Models\School;
use App\Services\TeacherWorkflow\LifecycleTransitionService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LifecycleTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_sets_deleted_record_back_to_inactive(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $service = new LifecycleTransitionService;

        $service->transition($content, LifecycleInput::delete());
        $restored = $service->transition($content->fresh(), LifecycleInput::restore());

        $this->assertSame('inactive', $restored->status);
        $this->assertNull($restored->deleted_at);
    }

    public function test_activate_invokes_validation_hook_once(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['status' => 'inactive']);
        $service = new LifecycleTransitionService;
        $calls = 0;

        $activated = $service->transition($content, LifecycleInput::activate(), function () use (&$calls): void {
            $calls++;
        });

        $this->assertSame(1, $calls);
        $this->assertSame('active', $activated->status);
    }

    public function test_invalid_transition_raises_conflict(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $service = new LifecycleTransitionService;

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Resource is already active.');

        $service->transition($content, LifecycleInput::activate());
    }
}
