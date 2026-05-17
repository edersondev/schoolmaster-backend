<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SpecsSubmoduleReadinessTest extends TestCase
{
    public function test_required_specs_source_files_are_available(): void
    {
        foreach ([
            base_path('specs/AGENTS.md'),
            base_path('specs/specs/001-schoolmaster-platform/spec.md'),
            base_path('specs/api/openapi.yaml'),
            base_path('specs/docs/backend-guidelines.md'),
            base_path('specs/docs/multi-tenant.md'),
            base_path('specs/docs/security.md'),
            base_path('specs/decisions/002-use-laravel-native-auth.md'),
            base_path('specs/decisions/003-use-mysql.md'),
            base_path('specs/decisions/004-use-tenant-by-column.md'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }
}
