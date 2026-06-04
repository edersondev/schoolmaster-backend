<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\AcademicRecordImportInput;
use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\ImportRun;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AcademicRecordImportService
{
    public function __construct(
        private readonly SchoolContextGuard $schools,
        private readonly TeacherWorkflowAuditLogger $audit,
    ) {}

    public function import(User $actor, TenantContext $context, AcademicRecordImportInput $input): ImportRun
    {
        $school = $this->schools->requireResolved($context);

        if (! Gate::forUser($actor)->allows('import', [ImportRun::class, $school->id])) {
            $this->audit->record('teacher_workflow.import', 'denied', $actor->id, $school->id, ImportRun::class, null, [
                'import_type' => $input->type,
                'row_count' => min(count($input->rows), 500),
            ]);

            throw new AuthorizationException('Grade and attendance imports require school administrator authority.');
        }

        $errors = $this->validateRows($input->type, $input->rows, $school->id);

        if ($errors !== []) {
            $run = $this->persistRun($actor, $school->id, $input->type, count($input->rows), 0, count($input->rows), 'rejected', $errors);

            $this->audit->record('teacher_workflow.import', 'rejected', $actor->id, $school->id, ImportRun::class, $run->uuid, [
                'import_type' => $input->type,
                'row_count' => count($input->rows),
                'accepted_row_count' => 0,
                'rejected_row_count' => count($input->rows),
                'error_summary' => $errors,
            ]);

            throw ValidationException::withMessages([
                'rows' => ['Import was rejected; no rows were committed.'],
                'error_summary' => $errors,
                'import_run_id' => [$run->uuid],
            ]);
        }

        return DB::transaction(function () use ($actor, $input, $school): ImportRun {
            $run = $this->persistRun($actor, $school->id, $input->type, count($input->rows), count($input->rows), 0, 'accepted', []);

            foreach ($input->rows as $row) {
                $student = StudentProfile::query()->where('uuid', $row['student_profile_id'])->where('school_id', $school->id)->firstOrFail();
                $period = AcademicPeriod::query()->where('uuid', $row['academic_period_id'])->where('school_id', $school->id)->firstOrFail();

                if ($input->type === 'grade') {
                    GradeRecord::query()->create([
                        'school_id' => $school->id,
                        'student_profile_id' => $student->id,
                        'academic_period_id' => $period->id,
                        'recorded_by_user_id' => $actor->id,
                        'original_recorded_by_user_id' => $actor->id,
                        'import_run_id' => $run->id,
                        'grade_value' => $row['grade_value'],
                        'grade_label' => $row['grade_label'] ?? null,
                        'status' => 'active',
                        'recorded_at' => now(),
                    ]);

                    continue;
                }

                AttendanceRecord::query()->create([
                    'school_id' => $school->id,
                    'student_profile_id' => $student->id,
                    'academic_period_id' => $period->id,
                    'recorded_by_user_id' => $actor->id,
                    'original_recorded_by_user_id' => $actor->id,
                    'import_run_id' => $run->id,
                    'attendance_date' => $row['attendance_date'],
                    'attendance_status' => $row['attendance_status'],
                    'status' => 'active',
                ]);
            }

            $this->audit->record('teacher_workflow.import', 'success', $actor->id, $school->id, ImportRun::class, $run->uuid, [
                'import_type' => $input->type,
                'row_count' => count($input->rows),
                'accepted_row_count' => count($input->rows),
                'rejected_row_count' => 0,
            ]);

            return $run->load(['school', 'actor']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{row:int, code:string, message:string}>
     */
    private function validateRows(string $type, array $rows, int $schoolId): array
    {
        $errors = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            if (! is_array($row)) {
                $errors[] = $this->rowError($rowNumber, 'malformed_row', 'Row must be an object.');

                continue;
            }

            $student = isset($row['student_profile_id'])
                ? StudentProfile::query()->where('uuid', $row['student_profile_id'])->where('school_id', $schoolId)->where('status', 'active')->first()
                : null;

            if ($student === null) {
                $errors[] = $this->rowError($rowNumber, 'invalid_reference', 'A referenced record is not valid for the resolved school.');
            }

            $period = isset($row['academic_period_id'])
                ? AcademicPeriod::query()->where('uuid', $row['academic_period_id'])->where('school_id', $schoolId)->where('status', 'active')->first()
                : null;

            if ($period === null) {
                $errors[] = $this->rowError($rowNumber, 'invalid_academic_period', 'Academic period is not active for imports in the resolved school.');
            }

            if ($student === null || $period === null) {
                continue;
            }

            $key = $type === 'grade'
                ? "{$student->id}:{$period->id}"
                : "{$student->id}:{$period->id}:".($row['attendance_date'] ?? '');

            if (isset($seen[$key])) {
                $errors[] = $this->rowError($rowNumber, 'duplicate_row', 'Row duplicates another row in this import.');
            }

            $seen[$key] = true;

            $exists = $type === 'grade'
                ? GradeRecord::withTrashed()->where('school_id', $schoolId)->where('student_profile_id', $student->id)->where('academic_period_id', $period->id)->exists()
                : AttendanceRecord::withTrashed()->where('school_id', $schoolId)->where('student_profile_id', $student->id)->where('academic_period_id', $period->id)->whereDate('attendance_date', $row['attendance_date'])->exists();

            if ($exists) {
                $errors[] = $this->rowError($rowNumber, 'duplicate_existing_record', 'Import rows must create new records only.');
            }
        }

        return $errors;
    }

    /**
     * @return array{row:int, code:string, message:string}
     */
    private function rowError(int $row, string $code, string $message): array
    {
        return ['row' => $row, 'code' => $code, 'message' => $message];
    }

    /**
     * @param  array<int, array{row:int, code:string, message:string}>  $errorSummary
     */
    private function persistRun(User $actor, int $schoolId, string $type, int $rowCount, int $acceptedRows, int $rejectedRows, string $status, array $errorSummary): ImportRun
    {
        return ImportRun::query()->create([
            'school_id' => $schoolId,
            'actor_user_id' => $actor->id,
            'import_type' => $type,
            'row_count' => min($rowCount, 500),
            'accepted_row_count' => min($acceptedRows, 500),
            'rejected_row_count' => min($rejectedRows, 500),
            'status' => $status,
            'error_summary' => array_slice($errorSummary, 0, 50),
        ])->load(['school', 'actor']);
    }
}
