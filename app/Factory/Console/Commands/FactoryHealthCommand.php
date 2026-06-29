<?php
declare(strict_types=1);
namespace App\Factory\Console\Commands;
use App\Factory\Services\FactoryHealthService; use Illuminate\Console\Command;
class FactoryHealthCommand extends Command { protected $signature='factory:health'; protected $description='Factory command'; public function handle(FactoryHealthService $service): int { $result=$service->check(); $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); return $result['status']==='healthy'?self::SUCCESS:self::FAILURE; } }
