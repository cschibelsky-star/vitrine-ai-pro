<?php

namespace App\Filament\Widgets;

use App\Models\License;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimasLicencasWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Últimas Licenças';

    public function table(Table $table): Table
    {
        return $table
            ->query(License::query()->with(['company', 'product'])->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('company.nome')->label('Cliente'),
                Tables\Columns\TextColumn::make('product.nome')->label('Produto'),
                Tables\Columns\TextColumn::make('plano')->label('Plano'),
                Tables\Columns\TextColumn::make('valor')->label('Valor')->money('BRL'),
                Tables\Columns\TextColumn::make('vencimento')->label('Vencimento')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'Ativa' => 'success',
                    'Trial' => 'info',
                    'Homologação' => 'warning',
                    'Suspensa', 'Cancelada' => 'danger',
                    default => 'gray',
                }),
            ])
            ->paginated(false);
    }
}
