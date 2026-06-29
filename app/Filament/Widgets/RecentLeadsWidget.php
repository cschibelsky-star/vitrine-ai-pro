<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLeadsWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimos Leads';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(Lead::query()->latest()->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('empresa')->label('Empresa')->searchable()->limit(28),
                Tables\Columns\TextColumn::make('produto_interesse')->label('Produto')->limit(24),
                Tables\Columns\TextColumn::make('plano_sugerido')->label('Plano')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
            ])
            ->paginated(false);
    }
}
