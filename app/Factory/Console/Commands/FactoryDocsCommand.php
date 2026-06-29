<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Documentation\Services\DocumentationGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryDocsCommand extends Command
{
    protected $signature = 'factory:docs {product_key}';
    protected $description = 'Gera documentação técnica de um produto Factory.';

    public function handle(DocumentationGenerator $generator): int
    {
        try {
            $result = $generator->generate((string) $this->argument('product_key'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Documentation Engine');
        $this->line('Produto: ' . $result['product']);
        $this->line('Arquivo: ' . $result['path']);

        return self::SUCCESS;
    }
}
