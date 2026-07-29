<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use Tests\TestCase;

final class MasterAccessContractDocumentationTest extends TestCase
{
    public function test_contract_defines_canonical_marker_and_non_permission_prerequisites(): void
    {
        $contractPath = base_path('specs/specs/031-system-admin-master/contracts/system-admin-master-contract.md');
        $securityPath = base_path('specs/docs/security.md');
        $openApiPath = base_path('specs/api/openapi.yaml');

        if (! is_file($contractPath) || ! is_file($securityPath) || ! is_file($openApiPath)) {
            $this->markTestSkipped('The external specifications checkout is unavailable.');
        }

        $contract = file_get_contents($contractPath);
        $security = file_get_contents($securityPath);
        $openApi = file_get_contents($openApiPath);

        $this->assertIsString($contract);
        $this->assertStringContainsString('`master_access_used: true`', $contract);
        $this->assertStringContainsString('tenant context, identity ownership, guardian-link state', $contract);
        $this->assertStringContainsString('approval workflows, explicit confirmations, support opt-ins, file safety', $contract);
        $this->assertIsString($security);
        $this->assertStringContainsString('Read-only access does not create new audit evidence solely', $security);
        $this->assertIsString($openApi);
        $this->assertStringContainsString('x-system-administrator-master-access:', $openApi);
        $this->assertStringContainsString('master_access_used', $openApi);
    }
}
