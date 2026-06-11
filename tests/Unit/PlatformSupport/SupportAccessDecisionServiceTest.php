<?php

declare(strict_types=1);

namespace Tests\Unit\PlatformSupport;

use App\Exceptions\ConflictException;
use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\TargetSchoolSupportOptIn;
use App\Services\PlatformSupport\SupportAccessDecisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportAccessDecisionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_rejects_expired_target_school_opt_in(): void
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $supportUser = $this->createPlatformUser(['platform_support.drill_down']);
        $optIn = TargetSchoolSupportOptIn::query()->create([
            'school_id' => $school->id,
            'requested_by_user_id' => $schoolAdmin->id,
            'approved_by_user_id' => $schoolAdmin->id,
            'state' => 'approved',
            'reason_code' => 'support_case',
            'purpose' => 'Diagnose reporting issue',
            'correlation_id' => 'case-expired',
            'approved_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        try {
            app(SupportAccessDecisionService::class)->request($supportUser, [
                'school_id' => $school->uuid,
                'support_opt_in_id' => $optIn->uuid,
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-expired',
            ]);

            $this->fail('Expected support access request to reject expired opt-in.');
        } catch (ConflictException) {
            $this->assertSame(1, PlatformSupportAuditEvent::query()
                ->where('action', 'conflict_detected')
                ->where('outcome', 'conflicted')
                ->where('reason_code', 'support_opt_in_inactive')
                ->where('correlation_id', 'case-expired')
                ->count());
        }
    }
}
