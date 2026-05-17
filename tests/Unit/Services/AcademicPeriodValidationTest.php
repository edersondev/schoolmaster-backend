<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\AcademicPeriods\CreateAcademicPeriodData;
use App\DTOs\TenantContext;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\AcademicPeriods\AcademicPeriodService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AcademicPeriodValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_period_sequence_is_unique_within_year(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $service = new AcademicPeriodService(new TenantContextService);
        $context = new TenantContext($school, 'test', 'resolved');
        $data = new CreateAcademicPeriodData($year->uuid, 'Term 1', 1, '2026-01-01', '2026-03-31');

        $service->create($actor, $context, $data);

        $this->expectException(ValidationException::class);

        $service->create($actor, $context, new CreateAcademicPeriodData($year->uuid, 'Duplicate', 1, '2026-04-01', '2026-06-30'));
    }
}
