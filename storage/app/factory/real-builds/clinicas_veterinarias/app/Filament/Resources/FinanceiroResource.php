<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Factory';

    protected static ?string $navigationLabel = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')->relationship('cliente', 'nome')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('descricao')->label('Descricao')->required(),
                    Forms\Components\TextInput::make('valor')->numeric()->label('Valor'),
                    Forms\Components\DatePicker::make('vencimento')->label('Vencimento'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nome')->label('Cliente')->searchable(),
                    Tables\Columns\TextColumn::make('descricao')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('valor')->money('BRL')->sortable(),
                    Tables\Columns\TextColumn::make('vencimento')->date()->sortable(),
                    Tables\Columns\TextColumn::make('status')->searchable()->sortable()
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiro::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),
            'view' => Pages\ViewFinanceiro::route('/{record}'),
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
        ];
    }
}
