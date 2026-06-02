<?php

declare(strict_types=1);

namespace App\Services\LearningSets;

use App\DTOs\LearningSets\CreateLearningSetData;
use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\LearningSet;
use App\Models\User;
use App\Services\Concerns\AuthorizesTeacherWorkflows;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LearningSetService
{
    use AuthorizesTeacherWorkflows;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly TeacherWorkflowListQuery $listQuery,
        private readonly LearningSetEntryValidator $entryValidator,
        private readonly LearningSetAssignmentValidator $assignmentValidator,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'learning_sets.view');

        return LearningSet::query()
            ->with([
                'school',
                'owner',
                'academicPeriod',
                'entries.contentItem',
                'entries.questionnaire',
                'assignments.studentProfile',
            ])
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateLearningSetData $data): LearningSet
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'learning_sets.manage');
        $academicPeriod = $this->activeAcademicPeriod($data->academicPeriodId, $school->id);
        $entries = $this->entryValidator->validate($data->entries, $school->id);
        $students = $this->assignmentValidator->validate($data->studentProfileIds, $school->id);

        return DB::transaction(function () use ($actor, $academicPeriod, $data, $entries, $school, $students): LearningSet {
            $learningSet = LearningSet::query()->create([
                'school_id' => $school->id,
                'owner_user_id' => $actor->id,
                'academic_period_id' => $academicPeriod->id,
                'title' => $data->title,
                'status' => 'published',
                'published_at' => now(),
            ]);

            foreach ($entries as $entry) {
                $learningSet->entries()->create([
                    'school_id' => $school->id,
                    'entry_type' => $entry['entry_type'],
                    'entry_reference_id' => $entry['entry_reference_id'],
                    'sequence' => $entry['sequence'],
                ]);
            }

            foreach ($students as $student) {
                $learningSet->assignments()->create([
                    'school_id' => $school->id,
                    'student_profile_id' => $student->id,
                    'assignment_mode' => 'legacy_direct',
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
            }

            return $learningSet->load([
                'school',
                'owner',
                'academicPeriod',
                'entries.contentItem',
                'entries.questionnaire',
                'assignments.studentProfile',
            ]);
        });
    }

    private function activeAcademicPeriod(string $periodUuid, int $schoolId): AcademicPeriod
    {
        $period = AcademicPeriod::query()
            ->where('uuid', $periodUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'academic_period_id' => ['The academic period must be active and belong to the resolved school.'],
            ]);
        }

        return $period;
    }
}
