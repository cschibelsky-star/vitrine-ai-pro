<?php

declare(strict_types=1);

namespace App\Factory\Engine\Contracts;

use App\Factory\Engine\DTO\ExecutionInput;
use App\Factory\Engine\DTO\ExecutionOutput;

interface AgentInterface
{
    public function handle(ExecutionInput $input): ExecutionOutput;
}
