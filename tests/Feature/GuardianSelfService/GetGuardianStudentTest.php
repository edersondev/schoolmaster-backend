<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\School;

final class GetGuardianStudentTest extends GuardianSelfServiceTestCase
{
    public function test_student_detail_returns_limited_summary_and_non_enumerating_not_found(): void
    {
        [$school, , , $guardianUser, $student] = $this->guardianContext();
        $otherSchool = School::factory()->create();
        $crossTenant = $this->student($otherSchool);
        $unassociated = $this->student($school);
        $inactive = $this->student($school, ['status' => 'inactive']);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $student->uuid)
            ->assertJsonMissing(['contact_email' => $student->contact_email]);

        $expected = $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson('/api/v1/guardian/students/'.\Illuminate\Support\Str::uuid())
            ->assertNotFound()
            ->json();

        foreach ([$crossTenant->uuid, $unassociated->uuid, $inactive->uuid] as $studentId) {
            $this->withHeaders($this->headers($guardianUser, $school))
                ->getJson("/api/v1/guardian/students/{$studentId}")
                ->assertNotFound()
                ->assertExactJson($expected);
        }
    }
}
