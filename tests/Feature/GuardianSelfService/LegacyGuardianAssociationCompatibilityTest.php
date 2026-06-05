<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class LegacyGuardianAssociationCompatibilityTest extends GuardianSelfServiceTestCase
{
    public function test_legacy_association_without_school_id_remains_visible(): void
    {
        [$school, , $guardian, $guardianUser, $student] = $this->guardianContext();

        $guardian->studentProfiles()->updateExistingPivot($student->id, ['school_id' => null]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson('/api/v1/guardian/students')
            ->assertOk()
            ->assertJsonFragment(['id' => $student->uuid]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}")
            ->assertOk();
    }
}
