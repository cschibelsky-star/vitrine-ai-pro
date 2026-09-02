<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\AI\Services\ViaSentinelService;
use Illuminate\Console\Command;

class ViaSentinelRun extends Command
{
    protected $signature = 'via:sentinel';

    protected $description = 'Executa um ciclo read-only do VIA Sentinel.';

    public function handle(ViaSentinelService $sentinel): int
    {
        $result = $sentinel->run();
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
