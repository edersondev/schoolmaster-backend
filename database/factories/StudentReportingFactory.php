<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReportOutput;
use App\Models\ReportRun;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

final class StudentReportingFactory
{
    public static function reportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return ReportRun::query()->create($attributes + [
            'school_id' => $school->id,
            'requested_by_user_id' => $requester->id,
            'report_type' => 'attendance',
            'filter_summary' => ['academic_period_id' => (string) Str::uuid()],
            'output_formats' => ['pdf', 'csv'],
            'status' => 'requested',
            'outputs_available' => false,
        ]);
    }

    public static function reportOutput(School $school, ReportRun $run, string $format = 'pdf', array $attributes = []): ReportOutput
    {
        $generatedAt = $attributes['generated_at'] ?? now();

        return ReportOutput::query()->create($attributes + [
            'school_id' => $school->id,
            'report_run_id' => $run->id,
            'format' => $format,
            'storage_path' => $school->uuid.'/'.$run->uuid.'/'.$format.'.'.$format,
            'generated_at' => $generatedAt,
            'expires_at' => $generatedAt->copy()->addDays(90),
            'status' => 'available',
        ]);
    }
}
