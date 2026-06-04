<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class GuardianSelfServiceTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{School, User, Guardian, User, StudentProfile, AcademicPeriod}
     */
    protected function guardianContext(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $guardianUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $guardian = $this->guardian($school);
        $student = $this->student($school, ['registration_number' => fake()->unique()->bothify('STU-###')]);
        $period = $this->academicPeriod($school);

        GuardianUserLink::query()->create([
            'school_id' => $school->id,
            'guardian_id' => $guardian->id,
            'user_id' => $guardianUser->id,
            'created_by_user_id' => $admin->id,
            'status' => 'active',
        ]);

        $guardian->studentProfiles()->attach($student->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'school_id' => $school->id,
            'relationship_type' => 'mother',
            'status' => 'active',
        ]);

        return [$school, $admin, $guardian, $guardianUser, $student, $period];
    }

    protected function headers(User $user, School $school): array
    {
        return [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($user),
            'X-School-Id' => $school->uuid,
        ];
    }

    protected function guardian(School $school, array $overrides = []): Guardian
    {
        return Guardian::query()->create($overrides + [
            'school_id' => $school->id,
            'full_name' => fake()->name(),
            'relationship_type' => 'guardian',
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'status' => 'active',
        ]);
    }

    protected function student(School $school, array $overrides = []): StudentProfile
    {
        return StudentProfile::query()->create($overrides + [
            'school_id' => $school->id,
            'registration_number' => fake()->unique()->bothify('STU-###'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'status' => 'active',
            'enrolled_at' => '2026-01-01',
            'status_effective_at' => '2026-01-01',
        ]);
    }

    protected function academicPeriod(School $school, string $status = 'active'): AcademicPeriod
    {
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => fake()->unique()->year(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => fake()->unique()->word(),
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => $status,
        ]);
    }
}
