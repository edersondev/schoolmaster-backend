<?php

declare(strict_types=1);

namespace Tests\Unit\GuardianSelfService;

use App\Models\Guardian;
use App\Models\School;
use App\Models\StudentProfile;
use App\Services\GuardianSelfService\GuardianVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianContactVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_visibility_includes_only_contract_approved_fields(): void
    {
        $school = School::factory()->create();
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian', 'relationship_type' => 'guardian', 'contact_email' => 'guardian@example.test', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'contact_email' => 'ada@example.test', 'status' => 'active']);
        $visibility = new GuardianVisibilityService;

        $this->assertSame(['guardian_id', 'full_name', 'contact_email', 'contact_phone'], array_keys($visibility->guardianContact($guardian)));
        $this->assertSame(['full_name', 'contact_email', 'contact_phone'], array_keys($visibility->studentPrimaryContact($student)));
    }
}
