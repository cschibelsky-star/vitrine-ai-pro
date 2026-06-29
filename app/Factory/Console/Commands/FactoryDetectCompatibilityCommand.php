<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Builder\Support\CompatibilityDetector;
use Illuminate\Console\Command;

class FactoryDetectCompatibilityCommand extends Command
{
    protected $signature = 'factory:detect-compatibility';
    protected $description = 'Detecta compatibilidade Laravel/Filament para a Factory.';

    public function handle(CompatibilityDetector $detector): int
    {
        $this->table(['Item', 'Valor'], collect($detector->detect())->map(fn ($v, $k) => [(string) $k, is_bool($v) ? ($v ? 'true' : 'false') : (string) $v])->values()->all());

        return self::SUCCESS;
    }
}
