<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class GetGuardianStudentAcademicsTransferTest extends GuardianSelfServiceTestCase
{
    public function test_transferred_student_academic_summary_is_hidden(): void
    {
        [$school, , , $guardianUser, $student, $period] = $this->guardianContext();
        $student->update(['status' => 'transferred']);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}/academics?academic_period_id={$period->uuid}")
            ->assertNotFound();
    }
}
