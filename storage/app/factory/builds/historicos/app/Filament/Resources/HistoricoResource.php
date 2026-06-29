<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoricoResource\Pages\CreateHistorico;
use App\Filament\Resources\HistoricoResource\Pages\EditHistorico;
use App\Filament\Resources\HistoricoResource\Pages\ListHistoricos;
use App\Filament\Resources\HistoricoResource\Pages\ViewHistorico;
use App\Models\Historico;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoricoResource extends Resource
{
    protected static ?string $model = Historico::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Históricos';
    protected static ?string $navigationLabel = 'Históricos';
    protected static ?string $slug = 'historicos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('fornecedor_id')->label('Fornecedor')->relationship('fornecedor', 'nome')->searchable()->preload(),
                    Textarea::make('descricao')->label('Descricao')->columnSpanFull(),
                    TextInput::make('tipo')->label('Tipo')->maxLength(255),
                    DatePicker::make('data_registro')->label('Data Registro'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fornecedor.nome')->label('Fornecedor')->searchable()->sortable(),
                TextColumn::make('descricao')->label('Descricao')->searchable()->sortable(),
                TextColumn::make('tipo')->label('Tipo')->searchable()->sortable(),
                TextColumn::make('data_registro')->label('Data Registro')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHistoricos::route('/'),
            'create' => CreateHistorico::route('/create'),
            'view' => ViewHistorico::route('/{record}'),
            'edit' => EditHistorico::route('/{record}/edit'),
        ];
    }
}
