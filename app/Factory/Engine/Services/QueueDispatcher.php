<?php

declare(strict_types=1);

namespace App\Factory\Engine\Services;

use App\Factory\Engine\Jobs\ExecuteBlueprintJob;
use App\Factory\Models\FactoryExecution;

class QueueDispatcher
{
    public function dispatch(FactoryExecution $execution): void
    {
        ExecuteBlueprintJob::dispatch($execution->id);
    }
}
