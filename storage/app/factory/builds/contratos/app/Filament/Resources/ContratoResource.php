<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratoResource\Pages\CreateContrato;
use App\Filament\Resources\ContratoResource\Pages\EditContrato;
use App\Filament\Resources\ContratoResource\Pages\ListContratos;
use App\Filament\Resources\ContratoResource\Pages\ViewContrato;
use App\Models\Contrato;
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

class ContratoResource extends Resource
{
    protected static ?string $model = Contrato::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Contratos';
    protected static ?string $navigationLabel = 'Contratos';
    protected static ?string $slug = 'contratos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('fornecedor_id')->label('Fornecedor')->relationship('fornecedor', 'nome')->searchable()->preload(),
                    Select::make('licitacao_id')->label('Licitacao')->relationship('licitacao', 'nome')->searchable()->preload(),
                    TextInput::make('numero')->label('Numero')->maxLength(255),
                    Textarea::make('objeto')->label('Objeto')->columnSpanFull(),
                    TextInput::make('valor')->label('Valor')->numeric(),
                    DatePicker::make('data_inicio')->label('Data Inicio'),
                    DatePicker::make('data_fim')->label('Data Fim'),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fornecedor.nome')->label('Fornecedor')->searchable()->sortable(),
                TextColumn::make('licitacao.nome')->label('Licitacao')->searchable()->sortable(),
                TextColumn::make('numero')->label('Numero')->searchable()->sortable(),
                TextColumn::make('objeto')->label('Objeto')->searchable()->sortable(),
                TextColumn::make('valor')->label('Valor')->searchable()->sortable(),
                TextColumn::make('data_inicio')->label('Data Inicio')->searchable()->sortable(),
                TextColumn::make('data_fim')->label('Data Fim')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->searchable()->sortable(),
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
            'index' => ListContratos::route('/'),
            'create' => CreateContrato::route('/create'),
            'view' => ViewContrato::route('/{record}'),
            'edit' => EditContrato::route('/{record}/edit'),
        ];
    }
}
