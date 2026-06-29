<?php

namespace App\Filament\Widgets;

use App\Models\AiQueue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiQueuesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $pending = class_exists(AiQueue::class) ? AiQueue::where('status', 'pendente')->count() : 0;
        $processing = class_exists(AiQueue::class) ? AiQueue::where('status', 'processando')->count() : 0;
        $finished = class_exists(AiQueue::class) ? AiQueue::where('status', 'concluido')->count() : 0;
        $errors = class_exists(AiQueue::class) ? AiQueue::where('status', 'erro')->count() : 0;

        return [
            Stat::make('Pendentes', $pending)->description('Aguardando processamento')->color('warning'),
            Stat::make('Processando', $processing)->description('Em execução')->color('info'),
            Stat::make('Concluídas', $finished)->description('Finalizadas com sucesso')->color('success'),
            Stat::make('Com erro', $errors)->description('Exigem análise')->color($errors > 0 ? 'danger' : 'gray'),
        ];
    }
}
