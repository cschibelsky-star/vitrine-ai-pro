<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryExecution;
class FactoryExecutionService { public function all() { return FactoryExecution::query()->latest()->get(); } }
