<?php

declare(strict_types=1);

namespace App\Factory\Engine\Services;

use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryExecutionLog;
use Illuminate\Support\Str;

class ExecutionLogger
{
    public function info(FactoryExecution $execution, string $event, string $message, array $payload = []): void
    {
        $this->log($execution, 'info', $event, $message, $payload);
    }

    public function error(FactoryExecution $execution, string $event, string $message, array $payload = []): void
    {
        $this->log($execution, 'error', $event, $message, $payload);
    }

    public function log(FactoryExecution $execution, string $level, string $event, string $message, array $payload = []): void
    {
        FactoryExecutionLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'factory_execution_id' => $execution->id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'payload' => $payload,
            'created_by' => auth()->id(),
        ]);
    }
}
