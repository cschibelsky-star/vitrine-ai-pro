<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;

class VendedorIaProNews extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = '03 · Comercial';
    protected static ?string $navigationLabel = 'VendedorIA Pro News';
    protected static ?string $title = 'VendedorIA Pro News';
    protected static ?string $slug = 'vendedoria-pro-news';
    protected static ?int $navigationSort = 17;

    protected static string $view = 'filament.pages.vendedor-ia-pro-news';

    public function getTotalLeads(): int
    {
        return Lead::query()
            ->where('origem_lead', 'VendedorIA Pro News')
            ->count();
    }

    public function getLeadsHoje(): int
    {
        return Lead::query()
            ->where('origem_lead', 'VendedorIA Pro News')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    public function getValorEstimado(): float
    {
        return (float) Lead::query()
            ->where('origem_lead', 'VendedorIA Pro News')
            ->sum('valor_estimado');
    }

    public function getUltimosLeads()
    {
        return Lead::query()
            ->where('origem_lead', 'VendedorIA Pro News')
            ->latest()
            ->take(10)
            ->get();
    }

    public function getLandingUrl(): string
    {
        return url('/vendedoria-pro-news/index.html');
    }

    public function getWidgetCode(): string
    {
        return '<script src="' . url('/assets/vendedoria-pro-news/widget.js') . '"></script>';
    }
}
