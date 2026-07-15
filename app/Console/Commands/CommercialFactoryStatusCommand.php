<?php

namespace App\Console\Commands;

use App\Commercial\Factory\Services\CommercialFactoryStatusService;
use Illuminate\Console\Command;

class CommercialFactoryStatusCommand extends Command
{
    protected $signature = 'commercial:factory-status';

    protected $description = 'Lista pedidos comerciais enviados para a Factory.';

    public function handle(CommercialFactoryStatusService $service): int
    {
        $items = $service->list();

        if (! count($items)) {
            $this->warn('Nenhum intake comercial encontrado.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                $this->toCell($item['project'] ?? '-'),
                $this->toCell($item['client'] ?? '-'),
                $this->toCell($item['product'] ?? '-'),
                $this->toCell($item['plan'] ?? '-'),
                $this->toCell($item['status'] ?? '-'),
            ];
        }

        $this->table(['Projeto', 'Cliente', 'Produto', 'Plano', 'Status'], $rows);

        return self::SUCCESS;
    }

    protected function toCell(mixed $value): string
    {
        if (is_null($value)) {
            return '-';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            if (isset($value['name']) && is_scalar($value['name'])) {
                return (string) $value['name'];
            }

            if (isset($value['label']) && is_scalar($value['label'])) {
                return (string) $value['label'];
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '-';
    }
}
