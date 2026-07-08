<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = '01 · Centro Operacional';

    protected static ?string $navigationLabel = 'Command Center';

    protected static ?string $title = 'Vitrine IA Pro Command Center';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.dashboard';

    public function countProjects(): int|string
    {
        $dir = storage_path('app/factory/blueprints');

        return File::isDirectory($dir) ? count(File::files($dir)) : '—';
    }

    public function countCommercialIntakes(): int|string
    {
        $dir = storage_path('app/factory/commercial-intake');

        return File::isDirectory($dir) ? count(File::directories($dir)) : '—';
    }

    public function getProducts(): array
    {
        return [
            [
                'name' => 'TV Digital Enterprise',
                'tag' => 'Produto SaaS',
                'status' => 'Comercial',
                'progress' => 92,
                'desc' => 'Portal TV com notícias, vídeos, RSS, transmissão ao vivo, banners, comercial e IA editorial.',
            ],
            [
                'name' => 'Guia Digital da Cidade',
                'tag' => 'Produto SaaS',
                'status' => 'Comercial',
                'progress' => 84,
                'desc' => 'Guia municipal replicável com atrativos, eventos, roteiros, turismo e comércio local.',
            ],
            [
                'name' => 'AssessorGov IA / GovTech',
                'tag' => 'Produto Estratégico',
                'status' => 'Factory',
                'progress' => 68,
                'desc' => 'Camada GovTech para oportunidades públicas, licitações, contratos e atendimento técnico.',
            ],
            [
                'name' => 'SISMED',
                'tag' => 'Roadmap',
                'status' => 'Em desenvolvimento',
                'progress' => 42,
                'desc' => 'Produto em desenvolvimento para gestão de saúde e atendimento institucional.',
            ],
        ];
    }
}
