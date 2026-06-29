<?php

declare(strict_types=1);

namespace App\Factory\Filament\Widgets;

use App\Factory\Services\FactoryDashboardService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Gate;

class FactoryExecutionsOverview extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Execuções por Status';

    protected function getData(): array
    {
        $data = app(FactoryDashboardService::class)->executionsByStatus();

        return [
            'datasets' => [
                [
                    'label' => 'Execuções',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_map(static fn (string $status): string => ucfirst($status), array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->check() && Gate::allows('factory.access');
    }
}
