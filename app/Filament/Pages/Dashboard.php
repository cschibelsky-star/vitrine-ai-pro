<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = '01 · Centro Operacional';

    protected static ?string $navigationLabel = 'Cockpit Executivo';

    protected static ?string $title = 'Cockpit Executivo';

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
                'desc' => 'Portal TV com notícias, vídeos, RSS, transmissão ao vivo, banners, comercial e IA editorial.',
            ],
            [
                'name' => 'Guia Digital da Cidade',
                'tag' => 'Produto SaaS',
                'status' => 'Comercial',
                'desc' => 'Guia municipal replicável com atrativos, eventos, roteiros, turismo e comércio local.',
            ],
            [
                'name' => 'Consultor AI GOV360',
                'tag' => 'Produto Estratégico',
                'status' => 'Factory',
                'desc' => 'Assistente para pequenas empresas venderem para o governo.',
            ],
            [
                'name' => 'SISMED',
                'tag' => 'Roadmap',
                'status' => 'Futuro',
                'desc' => 'Produto em desenvolvimento para gestão de saúde e atendimento.',
            ],
        ];
    }
}
