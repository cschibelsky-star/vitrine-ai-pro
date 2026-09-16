<?php

namespace App\Filament\Pages;

use App\Factory\Models\FactoryBlueprint;
use App\Factory\Models\FactoryCapability;
use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryProject;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = '01 · Factory';
    protected static ?string $navigationLabel = 'Cockpit';
    protected static ?string $title = 'Vitrine IA Pro · Factory';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.dashboard';

    public function metrics(): array
    {
        return [
            'projects' => FactoryProject::count(),
            'capabilities' => FactoryCapability::count(),
            'blueprints' => FactoryBlueprint::count(),
            'executions' => FactoryExecution::count(),
            'running' => FactoryExecution::where('status', 'running')->count(),
            'finished' => FactoryExecution::where('status', 'finished')->count(),
            'failed' => FactoryExecution::where('status', 'failed')->count(),
        ];
    }

    public function recentExecutions(): array
    {
        return FactoryExecution::query()
            ->with(['project:id,name', 'blueprint:id,name'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (FactoryExecution $execution) => [
                'name' => $execution->name ?: ($execution->blueprint?->name ?? 'Execução Factory'),
                'project' => $execution->project?->name ?? 'Sem projeto associado',
                'status' => $execution->status ?: '—',
                'duration' => $execution->duration_ms ? number_format($execution->duration_ms / 1000, 2, ',', '.') . 's' : '—',
            ])
            ->all();
    }

    public function stages(): array
    {
        return [
            ['key' => 'intake', 'code' => 'IN', 'title' => 'Intake & Demandas', 'detail' => 'Necessidades registradas e entrada operacional.'],
            ['key' => 'radar', 'code' => 'AR', 'title' => 'Análise & Radar', 'detail' => 'Aderência, oportunidades e análise técnica.'],
            ['key' => 'blueprint', 'code' => 'BP', 'title' => 'Blueprint', 'detail' => 'Arquitetura e plano técnico.'],
            ['key' => 'development', 'code' => 'DV', 'title' => 'Desenvolvimento', 'detail' => 'Código, componentes e integrações.'],
            ['key' => 'qa', 'code' => 'QA', 'title' => 'QA & Testes', 'detail' => 'Qualidade, validação e segurança.'],
            ['key' => 'hml', 'code' => 'HM', 'title' => 'Homologação (HML)', 'detail' => 'Ambientes de teste e validação.'],
            ['key' => 'release', 'code' => 'RL', 'title' => 'Release & Deploy', 'detail' => 'Publicação controlada e entrega.'],
            ['key' => 'docs', 'code' => 'DC', 'title' => 'Documentação', 'detail' => 'Documentos, evidências e histórico.'],
        ];
    }
}
