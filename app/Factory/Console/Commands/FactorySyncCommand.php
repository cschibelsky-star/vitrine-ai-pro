<?php
declare(strict_types=1);
namespace App\Factory\Console\Commands;
use App\Factory\Services\FactorySyncService; use Illuminate\Console\Command;
class FactorySyncCommand extends Command { protected $signature='factory:sync'; protected $description='Factory command'; public function handle(FactorySyncService $service): int { $this->line(json_encode($service->syncCoreDefaults(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); return self::SUCCESS; } }
