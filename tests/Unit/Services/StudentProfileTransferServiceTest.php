<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\StudentProfiles\TransferStudentProfileData;
use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\User;
use App\Services\StudentProfiles\StudentProfileTransferService;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_service_rejects_inactive_source_profile(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_transfers.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['status' => 'inactive']);

        $this->expectException(ConflictException::class);

        app(StudentProfileTransferService::class)->transfer($admin, $school, $profile, new TransferStudentProfileData(
            effectiveAt: '2026-04-01',
            reason: 'Rejected.',
            destinationSchoolId: null,
            destinationStudentProfileId: null,
        ));
    }
}
