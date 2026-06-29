<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimosClientesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Últimos Clientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(Company::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('produto_principal')->label('Produto'),
                Tables\Columns\TextColumn::make('responsavel')->label('Responsável'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'Ativo' => 'success',
                    'Homologação' => 'warning',
                    'Implantação' => 'info',
                    'Suspenso' => 'danger',
                    default => 'gray',
                }),
            ])
            ->paginated(false);
    }
}
