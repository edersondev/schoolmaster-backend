<?php

declare(strict_types=1);

namespace Tests\Unit\StudentProfiles;

use App\DTOs\StudentProfiles\StudentProfileGuardianEntryData;
use PHPUnit\Framework\TestCase;

final class StudentProfileGuardianEntryDataTest extends TestCase
{
    public function test_existing_guardian_entry_normalizes_reference_mode(): void
    {
        $entry = StudentProfileGuardianEntryData::fromArray([
            'guardian_id' => 'guardian-uuid',
            'relationship_type' => 'father',
        ]);

        $this->assertTrue($entry->isExistingGuardian());
        $this->assertFalse($entry->isNewGuardian());
        $this->assertSame('guardian-uuid', $entry->guardianId);
        $this->assertNull($entry->newGuardian);
    }

    public function test_new_guardian_entry_normalizes_identity_mode(): void
    {
        $entry = StudentProfileGuardianEntryData::fromArray([
            'relationship_type' => 'mother',
            'full_name' => 'Maria Costa',
            'contact_email' => 'maria@example.test',
            'contact_phone' => '+5511999999999',
        ]);

        $this->assertTrue($entry->isNewGuardian());
        $this->assertFalse($entry->isExistingGuardian());
        $this->assertSame('Maria Costa', $entry->newGuardian?->fullName);
        $this->assertSame('maria@example.test', $entry->newGuardian?->contactEmail);
    }
}
