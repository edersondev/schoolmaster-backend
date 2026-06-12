<?php

declare(strict_types=1);

namespace App\Services\Questionnaires;

use App\DTOs\Questionnaires\CreateQuestionnaireData;
use App\DTOs\TenantContext;
use App\Models\Questionnaire;
use App\Models\User;
use App\Services\Assessment\AssessmentAuditService;
use App\Services\Assessment\AssessmentTenantScopeService;
use App\Services\Concerns\AuthorizesTeacherWorkflows;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class QuestionnaireService
{
    use AuthorizesTeacherWorkflows;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly TeacherWorkflowListQuery $listQuery,
        private readonly QuestionnaireValidator $validator,
        private readonly AssessmentTenantScopeService $assessmentTenantScope,
        private readonly AssessmentAuditService $assessmentAudit,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'questionnaires.view');

        return Questionnaire::query()
            ->with(['school', 'owner', 'questions'])
            ->where('school_id', $school->id)
            ->orderBy('title')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateQuestionnaireData $data): Questionnaire
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'questionnaires.manage');
        $this->validator->validate($data->questions);

        return DB::transaction(function () use ($actor, $context, $data, $school): Questionnaire {
            $questionnaire = Questionnaire::query()->create([
                'school_id' => $school->id,
                'owner_user_id' => $actor->id,
                'title' => $data->title,
                'description' => $data->description,
                'status' => 'active',
            ]);

            foreach ($data->questions as $question) {
                $questionnaire->questions()->create([
                    'question_type' => $question['question_type'],
                    'prompt' => $question['prompt'],
                    'options' => $question['options'] ?? null,
                    'correct_answer' => $question['correct_answer'] ?? null,
                    'answer_schema' => $question['answer_schema'] ?? null,
                    'grading_rule' => $question['grading_rule'] ?? null,
                    'visibility' => $question['visibility'] ?? null,
                    'sequence' => $question['sequence'],
                ]);
            }

            $questionnaire->load(['school', 'owner', 'questions']);

            $this->assessmentAudit->record(
                $this->assessmentTenantScope->actorContext($actor, $context, 'teacher'),
                'authoring',
                'succeeded',
                'questionnaire_created',
                $questionnaire,
                ['question_count' => count($data->questions)],
            );

            return $questionnaire;
        });
    }
}
