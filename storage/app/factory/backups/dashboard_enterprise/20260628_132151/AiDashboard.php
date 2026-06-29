<?php

namespace App\Filament\Pages;

use App\Models\AiAgent;
use App\Models\AiAlert;
use App\Models\AiExecution;
use App\Models\AiProvider;
use Filament\Pages\Page;

class AiDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Dashboard IA';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Centro de IA';
    protected static ?string $slug = 'centro-ia';
    protected static string $view = 'filament.pages.ai-dashboard-enterprise';

    public function getViewData(): array
    {
        return [
            'agentsCount' => class_exists(AiAgent::class) ? AiAgent::count() : 0,
            'providersCount' => class_exists(AiProvider::class) ? AiProvider::count() : 0,
            'executionsToday' => class_exists(AiExecution::class) ? AiExecution::whereDate('created_at', today())->count() : 0,
            'openAlerts' => class_exists(AiAlert::class) ? AiAlert::whereIn('status', ['Aberto', 'Ativo', 'Pendente'])->count() : 0,
        ];
    }
}
