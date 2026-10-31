<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\ExecutionCostMetadataWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExecutionCostMetadataWriterTest extends TestCase
{
    public function test_rejects_unverified_data_before_database_access(): void
    {
        $this->expectException(RuntimeException::class);
        (new ExecutionCostMetadataWriter())->persist(42, [
            'status' => 'pending_provider_reconciliation',
            'charge_authoritative' => false,
            'request_id' => 'req-123',
            'currency' => 'BRL',
            'actual_cost_minor' => 0,
        ]);
    }

    public function test_rejects_missing_request_identifier(): void
    {
        $this->expectException(RuntimeException::class);
        (new ExecutionCostMetadataWriter())->persist(42, [
            'status' => 'verified', 'charge_authoritative' => true,
            'request_id' => '', 'currency' => 'BRL', 'actual_cost_minor' => 3,
        ]);
    }
}
