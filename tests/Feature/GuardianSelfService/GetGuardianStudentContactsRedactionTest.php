<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class GetGuardianStudentContactsRedactionTest extends GuardianSelfServiceTestCase
{
    public function test_contact_view_hides_other_guardians_and_non_primary_details(): void
    {
        [$school, , , $guardianUser, $student] = $this->guardianContext();
        $otherGuardian = $this->guardian($school, ['contact_email' => 'other@example.test']);
        $otherGuardian->studentProfiles()->attach($student->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'school_id' => $school->id,
            'relationship_type' => 'father',
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}/contacts")
            ->assertOk()
            ->assertJsonMissing(['contact_email' => 'other@example.test'])
            ->assertJsonMissing(['school_only_notes'])
            ->assertJsonMissing(['emergency_handling']);
    }
}
