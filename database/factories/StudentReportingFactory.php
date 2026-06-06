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
            'generation_status' => $attributes['status'] ?? 'requested',
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
            'availability' => $attributes['status'] ?? 'available',
        ]);
    }

    public static function requestedReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['status' => 'requested', 'generation_status' => 'requested']);
    }

    public static function generatingReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['status' => 'generating', 'generation_status' => 'generating']);
    }

    public static function generatedReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['status' => 'generated', 'generation_status' => 'generated', 'outputs_available' => true]);
    }

    public static function failedReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['status' => 'failed', 'generation_status' => 'failed']);
    }

    public static function canceledReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['status' => 'canceled', 'generation_status' => 'canceled']);
    }

    public static function deletedReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        $run = self::generatedReportRun($school, $requester, $attributes);
        $run->delete();

        return $run->refresh();
    }

    public static function retriedReportRun(School $school, User $requester, ReportRun $source, array $attributes = []): ReportRun
    {
        return self::reportRun($school, $requester, $attributes + ['source_report_run_id' => $source->id]);
    }

    public static function expiredOutputReportRun(School $school, User $requester, array $attributes = []): ReportRun
    {
        $run = self::generatedReportRun($school, $requester, $attributes);
        self::reportOutput($school, $run, 'pdf', ['expires_at' => now()->subDay(), 'availability' => 'expired']);

        return $run;
    }

    public static function pendingReportOutput(School $school, ReportRun $run, string $format = 'pdf', array $attributes = []): ReportOutput
    {
        return self::reportOutput($school, $run, $format, $attributes + ['status' => 'pending', 'availability' => 'pending']);
    }

    public static function availableReportOutput(School $school, ReportRun $run, string $format = 'pdf', array $attributes = []): ReportOutput
    {
        return self::reportOutput($school, $run, $format, $attributes + ['status' => 'available', 'availability' => 'available']);
    }

    public static function failedReportOutput(School $school, ReportRun $run, string $format = 'pdf', array $attributes = []): ReportOutput
    {
        return self::reportOutput($school, $run, $format, $attributes + ['status' => 'failed', 'availability' => 'failed']);
    }

    public static function expiredReportOutput(School $school, ReportRun $run, string $format = 'pdf', array $attributes = []): ReportOutput
    {
        return self::reportOutput($school, $run, $format, $attributes + ['status' => 'available', 'availability' => 'expired', 'expires_at' => now()->subDay()]);
    }

    public static function unsupportedReportOutput(School $school, ReportRun $run, string $format = 'xlsx', array $attributes = []): ReportOutput
    {
        return self::reportOutput($school, $run, $format, $attributes + ['status' => 'unsupported', 'availability' => 'unsupported']);
    }
}
