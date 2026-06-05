<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class GetGuardianStudentContactsTest extends GuardianSelfServiceTestCase
{
    public function test_guardian_contact_view_returns_own_contact_and_student_primary_contact(): void
    {
        [$school, , $guardian, $guardianUser, $student] = $this->guardianContext();

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}/contacts")
            ->assertOk()
            ->assertJsonPath('data.guardian_contact.guardian_id', $guardian->uuid)
            ->assertJsonPath('data.guardian_contact.contact_email', $guardian->contact_email)
            ->assertJsonPath('data.student_primary_contact.contact_email', $student->contact_email);
    }
}
