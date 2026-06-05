<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\User;

final class GuardianStudentTransferVisibilityTest extends GuardianSelfServiceTestCase
{
    public function test_transferred_source_student_is_hidden_and_destination_requires_separate_association(): void
    {
        [$school, , , $guardianUser, $student] = $this->guardianContext();
        $student->update(['status' => 'transferred']);
        $destination = School::factory()->create();
        $destinationUser = User::factory()->create(['school_id' => $destination->id, 'status' => 'active']);
        $destinationGuardian = $this->guardian($destination);
        $destinationStudent = $this->student($destination);

        GuardianUserLink::query()->create([
            'school_id' => $destination->id,
            'guardian_id' => $destinationGuardian->id,
            'user_id' => $destinationUser->id,
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($guardianUser, $school))
            ->getJson("/api/v1/guardian/students/{$student->uuid}")
            ->assertNotFound();

        $this->withHeaders($this->headers($destinationUser, $destination))
            ->getJson("/api/v1/guardian/students/{$destinationStudent->uuid}")
            ->assertNotFound();

        $destinationGuardian->studentProfiles()->attach($destinationStudent->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'school_id' => $destination->id,
            'relationship_type' => 'guardian',
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($destinationUser, $destination))
            ->getJson("/api/v1/guardian/students/{$destinationStudent->uuid}")
            ->assertOk();
    }
}
