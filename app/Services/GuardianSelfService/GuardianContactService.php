<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianContactViewQuery;

final class GuardianContactService
{
    public function __construct(private readonly GuardianVisibilityService $visibility) {}

    /**
     * @return array<string, mixed>
     */
    public function view(GuardianContactViewQuery $query): array
    {
        return [
            'student' => $this->visibility->studentSummary($query->target->student, $query->target->relationshipLabel),
            'guardian_contact' => $this->visibility->guardianContact($query->target->actor->guardian),
            'relationship_label' => $query->target->relationshipLabel,
            'student_primary_contact' => $this->visibility->studentPrimaryContact($query->target->student),
        ];
    }
}
