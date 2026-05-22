<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\StudentProfiles\CreateStudentProfileData;
use App\Models\EnrollmentHistory;
use App\Models\Guardian;
use App\Models\School;
use App\Services\StudentProfiles\StudentProfileCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class StudentProfileCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_creation_is_atomic_when_guardian_validation_fails(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);
        $guardian = Guardian::query()->create([
            'school_id' => $otherSchool->id,
            'full_name' => 'Other Guardian',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(StudentProfileCreationService::class)->create(
            $admin,
            $school,
            new CreateStudentProfileData(
                userId: null,
                registrationNumber: 'STU-006',
                firstName: 'Aline',
                lastName: 'Silva',
                dateOfBirth: null,
                contactEmail: null,
                contactPhone: null,
                currentAcademicYearId: null,
                status: 'active',
                enrolledAt: '2026-02-01',
                guardianAssociations: [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'parent'],
                ],
            ),
        );

        $this->assertDatabaseMissing('student_profiles', ['registration_number' => 'STU-006']);
        $this->assertSame(0, EnrollmentHistory::query()->count());
    }
}
