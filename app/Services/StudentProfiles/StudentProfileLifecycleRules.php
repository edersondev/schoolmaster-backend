<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\Exceptions\ConflictException;
use App\Models\StudentProfile;

final class StudentProfileLifecycleRules
{
    /** @var array<string, array<int, string>> */
    private const NON_TRANSFER_TRANSITIONS = [
        'active' => ['inactive'],
        'inactive' => ['active'],
    ];

    public function assertNonTransferTransition(StudentProfile $studentProfile, string $targetStatus): void
    {
        if ($targetStatus === 'transferred') {
            throw new ConflictException('Transfer status must be applied through the transfer operation.');
        }

        if ($studentProfile->status === $targetStatus) {
            throw new ConflictException('Student profile already has the requested status.');
        }

        $allowed = self::NON_TRANSFER_TRANSITIONS[$studentProfile->status] ?? [];

        if (! in_array($targetStatus, $allowed, true)) {
            throw new ConflictException('Student profile lifecycle transition is not supported.');
        }
    }

    public function eventTypeFor(string $targetStatus): string
    {
        return match ($targetStatus) {
            'active' => 'activated',
            'inactive' => 'inactivated',
            default => throw new ConflictException('Student profile lifecycle transition is not supported.'),
        };
    }
}
