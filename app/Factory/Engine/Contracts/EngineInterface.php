<?php

declare(strict_types=1);

namespace App\Factory\Engine\Contracts;

use App\Factory\Models\FactoryExecution;

interface EngineInterface
{
    public function execute(FactoryExecution $execution): FactoryExecution;
}
