<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryExecutionLog;
class FactoryLogService { public function all() { return FactoryExecutionLog::query()->latest()->get(); } }
