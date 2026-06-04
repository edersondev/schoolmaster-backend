<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class ListGuardianStudentsTest extends GuardianSelfServiceTestCase
{
    public function test_guardian_lists_only_active_associated_students_in_resolved_school(): void
    {
        [$school, , $guardian, $guardianUser, $student] = $this->guardianContext();
        $inactive = $this->student($school, ['status' => 'inactive']);
        $unassociated = $this->student($school);

        $guardian->studentProfiles()->attach($inactive->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'school_id' => $school->id,
            'relationship_type' => 'father',
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson('/api/v1/guardian/students')
            ->assertOk()
            ->assertJsonPath('data.0.id', $student->uuid)
            ->assertJsonMissing(['id' => $inactive->uuid])
            ->assertJsonMissing(['id' => $unassociated->uuid]);
    }
}
