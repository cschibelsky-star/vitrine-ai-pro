<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\ExecutionCostReconciler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExecutionCostReconcilerTest extends TestCase
{
    private function context(): array
    {
        return ['request_id' => 'req-1', 'provider' => 'invision', 'model_id' => 'model',
            'currency' => 'BRL', 'scope' => 'project:chat'];
    }

    public function test_verified_receipt_is_attributed_without_estimating_charge(): void
    {
        $result = (new ExecutionCostReconciler())->reconcile([
            'input_tokens' => 42000, 'output_tokens' => 500,
            'actual_cost_minor' => 24, 'verified_provider_receipt' => true,
        ], $this->context());
        self::assertSame('verified', $result['status']);
        self::assertSame(24, $result['actual_cost_minor']);
        self::assertTrue($result['charge_authoritative']);
    }

    public function test_missing_receipt_must_not_be_treated_as_zero_cost(): void
    {
        $result = (new ExecutionCostReconciler())->reconcile([
            'input_tokens' => 42000, 'output_tokens' => 500,
        ], $this->context());
        self::assertSame('pending_provider_reconciliation', $result['status']);
        self::assertFalse($result['charge_authoritative']);
        self::assertArrayNotHasKey('actual_cost_minor', $result);
    }

    public function test_missing_request_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ExecutionCostReconciler())->reconcile(
            ['input_tokens' => 1, 'output_tokens' => 1],
            array_merge($this->context(), ['request_id' => ''])
        );
    }
}
