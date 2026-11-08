<?php
declare(strict_types=1);
namespace Tests\Unit\Marketing;
use App\Marketing\Governance\AutonomousHandoffGate;
use PHPUnit\Framework\TestCase;

// Regression tests for the structural OBSERVER contract; CI supplies PHP/PHPUnit.
final class AutonomousHandoffReturnSafetyTest extends TestCase
{
    private function fixture(): array
    {
        $path = dirname(__DIR__, 2).'/Fixtures/Marketing/handoff_via_observer_blocked.json';
        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_rejected_review_cannot_be_overridden_by_next_owner(): void
    {
        $data = $this->fixture();
        $data['state'] = 'ready_for_via';
        $data['next_owner'] = 'devops';
        $data['review'] = ['status'=>'rejected', 'reviewer_agent_id'=>'qa', 'evidence'=>'synthetic'];
        $result = (new AutonomousHandoffGate())->evaluate($data);
        self::assertSame('blocked', $result['decision']);
        self::assertFalse($result['can_advance']);
        self::assertContains('independent review missing or invalid', $result['failures']);
    }

    public function test_correction_state_is_not_ready_for_handoff(): void
    {
        $data = $this->fixture();
        $data['state'] = 'needs_correction';
        $result = (new AutonomousHandoffGate())->evaluate($data);
        self::assertSame('blocked', $result['decision']);
        self::assertFalse($result['can_advance']);
        self::assertContains('state must be ready_for_via', $result['failures']);
    }

    public function test_review_text_does_not_authenticate_new_commit(): void
    {
        $data = $this->fixture();
        $data['state'] = 'ready_for_via';
        $data['result_sha'] = str_repeat('c', 40);
        $data['review'] = ['status'=>'approved', 'reviewer_agent_id'=>'qa', 'evidence'=>'old synthetic approval'];
        $result = (new AutonomousHandoffGate())->evaluate($data);
        self::assertSame('pending_external_verification', $result['decision']);
        self::assertFalse($result['can_advance']);
        self::assertFalse($result['evidence_verified']);
    }
}
