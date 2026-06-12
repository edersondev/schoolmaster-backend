<?php

declare(strict_types=1);

namespace App\DTOs\PlatformSupport;

use App\Models\School;

final readonly class PlatformSupportTargetSchool
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $status,
    ) {}

    public static function fromSchool(School $school): self
    {
        return new self(
            id: $school->id,
            uuid: $school->uuid,
            status: $school->status,
        );
    }
}
