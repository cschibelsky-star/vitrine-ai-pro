<?php

namespace Tests\Unit\Cockpit;

use PHPUnit\Framework\TestCase;

final class HomologationWorkflowConfigTest extends TestCase
{
    public function test_iterative_workflow_keeps_published_release_separate(): void
    {
        $policy = require __DIR__ . '/../../../config/cockpit_homologation.php';
        self::assertArrayHasKey('em_teste', $policy['phases']);
        self::assertArrayHasKey('correcao_pendente', $policy['phases']);
        self::assertArrayHasKey('aprovado', $policy['phases']);
        self::assertContains('hml_commit_sha', $policy['required_release_fields']);
        self::assertContains('production_commit_sha', $policy['required_release_fields']);
        self::assertTrue($policy['invalidate_approval_on_new_sha']);
    }

    public function test_login_through_release_workflow_is_part_of_homologation(): void
    {
        $policy = require __DIR__ . '/../../../config/cockpit_homologation.php';
        self::assertArrayHasKey('acesso', $policy['test_areas']);
        self::assertArrayHasKey('integracoes', $policy['test_areas']);
        self::assertContains('melhoria', $policy['issue_types']);
        self::assertContains('em_reteste', $policy['issue_statuses']);
    }
}
