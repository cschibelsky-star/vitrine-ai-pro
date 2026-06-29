<?php

declare(strict_types=1);

namespace App\Factory\Engine\Contracts;

use App\Factory\Models\FactoryExecution;

interface ExecutionInterface
{
    public function run(FactoryExecution $execution): FactoryExecution;
}
