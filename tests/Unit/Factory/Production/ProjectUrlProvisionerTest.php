<?php

declare(strict_types=1);

namespace Tests\Unit\Factory\Production;

use App\Factory\Production\Services\ProjectUrlProvisioner;
use PHPUnit\Framework\TestCase;

final class ProjectUrlProvisionerTest extends TestCase
{
    public function test_project_policy_uses_immutable_hml_code(): void
    {
        $result = (new ProjectUrlProvisioner())->provisionCommand('jarvis', 'jarvis_hml_agent:8000', '/health', 'jarvis');
        $this->assertSame('p######.hml.vitrineiapro.com.br', $result['identity_policy']);
        $this->assertContains('--friendly', $result['arguments']);
    }

    public function test_customer_policy_uses_short_root_code(): void
    {
        $result = (new ProjectUrlProvisioner())->provisionCommand('produto-x', 'produto_x:80', '/health', 'cliente-x', 'customer');
        $this->assertSame('c######.vitrineiapro.com.br', $result['identity_policy']);
    }
}
