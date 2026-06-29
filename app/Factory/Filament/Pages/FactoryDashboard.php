<?php

declare(strict_types=1);

namespace App\Factory\Filament\Pages;

use App\Factory\Services\FactoryDashboardService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

class FactoryDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Factory 2.0';
    protected static ?string $slug = 'factory-dashboard';
    protected static ?string $navigationGroup = 'Factory 2.0';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'factory.filament.pages.factory-dashboard';

    public array $stats = [];
    public array $executionsByStatus = [];

    public function mount(FactoryDashboardService $dashboardService): void
    {
        $this->stats = $dashboardService->stats();
        $this->executionsByStatus = $dashboardService->executionsByStatus();
    }

    public static function canAccess(): bool
    {
        return auth()->check() && Gate::allows('factory.access');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Factory 2.0';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
