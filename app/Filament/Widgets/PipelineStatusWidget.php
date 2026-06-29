<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class PipelineStatusWidget extends BaseWidget
{
    protected static ?string $heading = 'Resumo do Funil Comercial';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        $query = Lead::query()
            ->selectRaw('MIN(id) as id, COALESCE(status_negociacao, "Novo / Não informado") as status_negociacao, COUNT(*) as total')
            ->groupBy('status_negociacao')
            ->orderByDesc('total');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('status_negociacao')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
