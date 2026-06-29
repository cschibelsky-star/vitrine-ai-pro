<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FactoryEvolutionCommand extends Command
{
    protected $signature = 'factory:evolution';
    protected $description = 'Registra log de evolução da Factory.';

    public function handle(): int
    {
        $dir = storage_path('app/factory/evolution');
        File::ensureDirectoryExists($dir);

        $entry = [
            'version' => config('factory_release.version'),
            'macro_pack' => config('factory_release.macro_pack'),
            'message' => 'Factory evoluída para arquitetura v3.0.',
            'recorded_at' => now()->toISOString(),
        ];

        $path = $dir . '/evolution_' . date('Ymd_His') . '.json';
        File::put($path, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Evolution Log registrado.');
        $this->line('Arquivo: ' . $path);

        return self::SUCCESS;
    }
}
