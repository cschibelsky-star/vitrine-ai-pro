<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimosLeadsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Últimos Leads';

    public function table(Table $table): Table
    {
        return $table
            ->query(Lead::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('empresa')->label('Empresa')->searchable(),
                Tables\Columns\TextColumn::make('contato')->label('Contato'),
                Tables\Columns\TextColumn::make('produto_interesse')->label('Produto de Interesse'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'Novo' => 'info',
                    'Contato' => 'primary',
                    'Demonstração', 'Proposta' => 'warning',
                    'Fechado' => 'success',
                    'Perdido' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->date('d/m/Y'),
            ])
            ->paginated(false);
    }
}
