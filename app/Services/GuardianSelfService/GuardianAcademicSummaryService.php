<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianAcademicSummaryQuery;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\LearningSetAssignment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GuardianAcademicSummaryService
{
    public function __construct(private readonly GuardianVisibilityService $visibility) {}

    public function resolveAcademicPeriod(string $academicPeriodUuid, int $schoolId): AcademicPeriod
    {
        $period = AcademicPeriod::query()
            ->where('uuid', $academicPeriodUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($period === null) {
            throw (new ModelNotFoundException)->setModel(AcademicPeriod::class);
        }

        return $period;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(GuardianAcademicSummaryQuery $query): array
    {
        return [
            'student' => $this->visibility->studentSummary($query->target->student, $query->target->relationshipLabel),
            'academic_period_id' => $query->academicPeriod->uuid,
            'grade_summary' => $this->gradeSummary($query),
            'attendance_summary' => $this->attendanceSummary($query),
            'learning_sets' => $this->learningSetSummaries($query),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gradeSummary(GuardianAcademicSummaryQuery $query): array
    {
        $records = GradeRecord::query()
            ->where('school_id', $query->target->actor->school->id)
            ->where('student_profile_id', $query->target->student->id)
            ->where('academic_period_id', $query->academicPeriod->id)
            ->where('status', 'active')
            ->get();

        return [
            'status' => $records->isEmpty() ? 'not_available' : 'available',
            'average' => $records->isEmpty() ? null : round((float) $records->avg('grade_value'), 2),
            'scale' => null,
            'last_updated_at' => $records->max('updated_at')?->toJSON(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSummary(GuardianAcademicSummaryQuery $query): array
    {
        $records = AttendanceRecord::query()
            ->where('school_id', $query->target->actor->school->id)
            ->where('student_profile_id', $query->target->student->id)
            ->where('academic_period_id', $query->academicPeriod->id)
            ->where('status', 'active')
            ->get();

        $total = $records->count();
        $absences = $records->where('attendance_status', 'absent')->count();
        $tardies = $records->whereIn('attendance_status', ['late', 'tardy'])->count();

        return [
            'status' => $total === 0 ? 'not_available' : 'available',
            'total_absences' => $absences,
            'total_tardies' => $tardies,
            'attendance_rate' => $total === 0 ? null : round((($total - $absences) / $total) * 100, 2),
            'last_updated_at' => $records->max('updated_at')?->toJSON(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function learningSetSummaries(GuardianAcademicSummaryQuery $query): array
    {
        return LearningSetAssignment::query()
            ->with('learningSet')
            ->where('school_id', $query->target->actor->school->id)
            ->where('student_profile_id', $query->target->student->id)
            ->where('status', 'active')
            ->whereHas('learningSet', fn ($builder) => $builder
                ->where('school_id', $query->target->actor->school->id)
                ->where('academic_period_id', $query->academicPeriod->id)
                ->whereIn('status', ['published', 'active']))
            ->get()
            ->map(fn (LearningSetAssignment $assignment): array => [
                'learning_set_id' => $assignment->learningSet?->uuid,
                'title' => $assignment->learningSet?->title,
                'status' => $assignment->status,
                'progress_percent' => null,
                'last_activity_at' => $assignment->assigned_at?->toJSON(),
            ])
            ->values()
            ->all();
    }
}
