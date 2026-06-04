<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\School;

final class GetGuardianStudentAcademicsPeriodTest extends GuardianSelfServiceTestCase
{
    public function test_academic_summary_requires_active_same_school_period(): void
    {
        [$school, , , $guardianUser, $student] = $this->guardianContext();
        $inactive = $this->academicPeriod($school, 'inactive');
        $otherSchool = School::factory()->create();
        $otherPeriod = $this->academicPeriod($otherSchool);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}/academics")
            ->assertUnprocessable();

        foreach ([$inactive->uuid, $otherPeriod->uuid] as $periodId) {
            $this->withHeaders($this->headers($guardianUser, $school))
                ->getJson("/api/v1/guardian/students/{$student->uuid}/academics?academic_period_id={$periodId}")
                ->assertNotFound();
        }
    }
}
