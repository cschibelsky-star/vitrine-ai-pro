<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Release\Services\FactoryReleaseManager;
use Illuminate\Console\Command;

class FactoryReleaseStatusCommand extends Command
{
    protected $signature = 'factory:release-status';
    protected $description = 'Exibe o status da release atual da Factory.';

    public function handle(FactoryReleaseManager $manager): int
    {
        $status = $manager->status();
        $path = $manager->registerRelease();

        $this->info('Factory Release Status');
        $this->line('Release: ' . $status['release']);
        $this->line('Version: ' . $status['version']);
        $this->line('Macro Pack: ' . $status['macro_pack']);
        $this->line('Status: ' . $status['status']);
        $this->line('Arquivo: ' . $path);

        $this->table(['Engine'], array_map(fn ($engine) => [$engine], $status['engines']));

        return self::SUCCESS;
    }
}
