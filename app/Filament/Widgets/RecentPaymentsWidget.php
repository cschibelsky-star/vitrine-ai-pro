<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimas Cobranças';
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    protected function amountColumn(): string
    {
        foreach (['amount', 'valor', 'value'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                return $column;
            }
        }

        return 'id';
    }

    protected function dueDateColumn(): ?string
    {
        foreach (['due_date', 'vencimento', 'data_vencimento'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                return $column;
            }
        }

        return null;
    }

    public function table(Table $table): Table
    {
        $amountColumn = $this->amountColumn();
        $dueDateColumn = $this->dueDateColumn();

        $columns = [
            Tables\Columns\TextColumn::make('description')
                ->label('Descrição')
                ->formatStateUsing(fn ($state) => $state ?: 'Cobrança sem descrição')
                ->limit(35),

            Tables\Columns\TextColumn::make($amountColumn)
                ->label('Valor')
                ->money('BRL'),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge(),
        ];

        if ($dueDateColumn) {
            $columns[] = Tables\Columns\TextColumn::make($dueDateColumn)
                ->label('Vencimento')
                ->date('d/m/Y');
        }

        return $table
            ->query(Payment::query()->latest()->limit(8))
            ->columns($columns)
            ->paginated(false);
    }
}
