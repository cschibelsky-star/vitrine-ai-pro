<?php

declare(strict_types=1);

namespace App\Factory\Engine\Services;

use App\Factory\Engine\Contracts\EngineInterface;
use App\Factory\Models\FactoryExecution;
use Throwable;

class FactoryEngine implements EngineInterface
{
    public function __construct(
        protected BlueprintExecutor $executor,
        protected ExecutionLogger $logger,
    ) {
    }

    public function execute(FactoryExecution $execution): FactoryExecution
    {
        try {
            $execution->markAsRunning();

            $this->logger->info($execution, 'engine_started', 'Factory Engine iniciou a execução.');

            $output = $this->executor->execute($execution);

            if (! $output->success) {
                $execution->markAsFailed($output->message ?? 'Falha sem mensagem.', $output->metadata);
                return $execution->refresh();
            }

            $execution->markAsFinished($output->toArray());

            $this->logger->info($execution->refresh(), 'engine_finished', 'Factory Engine finalizou a execução.');

            return $execution->refresh();
        } catch (Throwable $exception) {
            $execution->markAsFailed($exception->getMessage(), [
                'exception' => $exception::class,
            ]);

            $this->logger->error($execution->refresh(), 'engine_failed', $exception->getMessage(), [
                'exception' => $exception::class,
            ]);

            return $execution->refresh();
        }
    }
}
