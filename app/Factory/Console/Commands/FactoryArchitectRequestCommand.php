<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Finalization\Services\AiArchitectFinalService;
use Illuminate\Console\Command;
use Throwable;

class FactoryArchitectRequestCommand extends Command
{
    protected $signature = 'factory:architect-request {request* : Solicitação livre}';
    protected $description = 'Cria arquitetura e blueprint a partir de uma solicitação livre.';

    public function handle(AiArchitectFinalService $architect): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $result = $architect->architect($request);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Arquitetura criada.');
        $this->line('Domínio: ' . $result['domain']);
        $this->line('Sistema: ' . $result['blueprint']['name']);
        $this->line('Slug: ' . $result['blueprint']['slug']);
        $this->line('Blueprint: ' . $result['blueprint_path']);
        $this->table(['Módulo'], array_map(fn ($module) => [$module['slug']], $result['blueprint']['modules']));

        return self::SUCCESS;
    }
}
