<?php

declare(strict_types=1);

namespace App\Factory\Engine\Jobs;

use App\Factory\Engine\Services\FactoryEngine;
use App\Factory\Models\FactoryExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteBlueprintJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $executionId,
    ) {
    }

    public function handle(FactoryEngine $engine): void
    {
        $execution = FactoryExecution::query()->findOrFail($this->executionId);

        $engine->execute($execution);
    }
}
