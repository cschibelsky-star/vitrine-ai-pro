<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductInterestWidget extends BaseWidget
{
    protected static ?string $heading = 'Produtos com Maior Interesse';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        $query = Lead::query()
            ->selectRaw('MIN(id) as id, COALESCE(produto_interesse, "Não informado") as produto_interesse, COUNT(*) as total')
            ->groupBy('produto_interesse')
            ->orderByDesc('total');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('produto_interesse')
                    ->label('Produto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Leads')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
