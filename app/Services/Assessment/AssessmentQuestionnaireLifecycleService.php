<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Models\Questionnaire;
use App\Services\TeacherWorkflow\HistoricalMeaningGuard;

final class AssessmentQuestionnaireLifecycleService
{
    public function __construct(private readonly HistoricalMeaningGuard $historicalMeaning) {}

    public function assertAdvancedQuestionChangesAllowed(Questionnaire $questionnaire, array $changes): void
    {
        $this->historicalMeaning->assertQuestionnaireEditable($questionnaire, $changes);
    }
}
