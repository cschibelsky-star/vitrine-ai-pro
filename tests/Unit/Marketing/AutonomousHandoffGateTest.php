<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Governance\AutonomousHandoffGate;
use PHPUnit\Framework\TestCase;

final class AutonomousHandoffGateTest extends TestCase
{
    /** @return array<string, mixed> */
    private function validHandoff(): array
    {
        return [
            'schema_version' => '1.0',
            'task_id' => 'MK-TEST-001',
            'repository' => 'cschibelsky-star/vitrine-ai-pro',
            'branch' => 'feature/marketing-test-isolated',
            'agent_id' => 'developer-a',
            'specialty' => 'developer',
            'objective' => 'offline handoff validation',
            'authorization' => [
                'scope' => 'offline tests',
                'approved_for_branch_write' => true,
                'production_approved' => false,
            ],
            'base_sha' => str_repeat('a', 40),
            'result_sha' => str_repeat('b', 40),
            'changed_files' => ['tests/Unit/Marketing/AutonomousHandoffGateTest.php'],
            'checks' => [[
                'id' => 'lint',
                'required' => true,
                'status' => 'passed',
                'command' => 'php -l example.php',
                'evidence' => 'synthetic fixture for local unit test only',
            ]],
            'review' => [
                'reviewer_agent_id' => 'qa-b',
                'status' => 'approved',
                'evidence' => 'synthetic fixture for local unit test only',
            ],
            'risks' => [],
            'next_owner' => 'via',
            'state' => 'ready_for_via',
        ];
    }

    public function test_valid_metadata_never_automatically_approves_progression(): void
    {
        $result = (new AutonomousHandoffGate())->evaluate($this->validHandoff());
        self::assertSame('pending_external_verification', $result['decision']);
        self::assertFalse($result['can_advance']);
        self::assertFalse($result['evidence_verified']);
        self::assertSame([], $result['failures']);
    }

    public function test_unrun_required_check_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['checks'][0]['status'] = 'not_run';
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_failed_required_check_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['checks'][0]['status'] = 'failed';
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_self_review_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['review']['reviewer_agent_id'] = 'developer-a';
        self::assertContains('independent review missing or invalid', (new AutonomousHandoffGate())->evaluate($data)['failures']);
    }

    public function test_missing_authorization_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['authorization']['approved_for_branch_write'] = false;
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_production_approval_in_offline_handoff_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['authorization']['production_approved'] = true;
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_equal_sha_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['result_sha'] = $data['base_sha'];
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_main_branch_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['branch'] = 'main';
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }

    public function test_missing_evidence_blocks_handoff(): void
    {
        $data = $this->validHandoff();
        $data['checks'][0]['evidence'] = ' ';
        self::assertSame('blocked', (new AutonomousHandoffGate())->evaluate($data)['decision']);
    }
}
