<?php

declare(strict_types=1);

namespace App\Services\LearningSets;

use App\Models\Questionnaire;
use App\Models\TeacherContentItem;
use Illuminate\Validation\ValidationException;

final class LearningSetEntryValidator
{
    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{entry_type: string, entry_reference_id: int, sequence: int}>
     */
    public function validate(array $entries, int $schoolId): array
    {
        $validated = [];
        $sequences = [];

        foreach ($entries as $index => $entry) {
            $extra = array_diff(array_keys($entry), ['entry_type', 'entry_reference_id', 'sequence']);
            if ($extra !== []) {
                throw ValidationException::withMessages(["entries.$index" => ['Entry contains undocumented fields.']]);
            }

            if (in_array($entry['sequence'], $sequences, true)) {
                throw ValidationException::withMessages(['entries' => ['Entry sequences must be unique.']]);
            }

            $reference = $this->resolveReference($entry['entry_type'], $entry['entry_reference_id'], $schoolId);
            $validated[] = [
                'entry_type' => $entry['entry_type'],
                'entry_reference_id' => $reference->id,
                'sequence' => (int) $entry['sequence'],
            ];
            $sequences[] = $entry['sequence'];
        }

        return $validated;
    }

    private function resolveReference(string $entryType, string $referenceUuid, int $schoolId): TeacherContentItem|Questionnaire
    {
        if ($entryType === 'content_item') {
            $content = TeacherContentItem::query()
                ->where('uuid', $referenceUuid)
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->where('scan_status', 'clean')
                ->first();

            if ($content === null) {
                throw ValidationException::withMessages([
                    'entries' => ['Content entries must exist, be active, same-school, and malware scan clean.'],
                ]);
            }

            return $content;
        }

        $questionnaire = Questionnaire::query()
            ->where('uuid', $referenceUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($questionnaire === null) {
            throw ValidationException::withMessages([
                'entries' => ['Questionnaire entries must exist, be active, and same-school.'],
            ]);
        }

        return $questionnaire;
    }
}
