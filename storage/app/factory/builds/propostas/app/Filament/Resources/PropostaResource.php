<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropostaResource\Pages\CreateProposta;
use App\Filament\Resources\PropostaResource\Pages\EditProposta;
use App\Filament\Resources\PropostaResource\Pages\ListPropostas;
use App\Filament\Resources\PropostaResource\Pages\ViewProposta;
use App\Models\Proposta;
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

class PropostaResource extends Resource
{
    protected static ?string $model = Proposta::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Propostas';
    protected static ?string $navigationLabel = 'Propostas';
    protected static ?string $slug = 'propostas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('licitacao_id')->label('Licitacao')->relationship('licitacao', 'nome')->searchable()->preload(),
                    Select::make('fornecedor_id')->label('Fornecedor')->relationship('fornecedor', 'nome')->searchable()->preload(),
                    TextInput::make('valor')->label('Valor')->numeric(),
                    TextInput::make('status')->label('Status')->maxLength(255),
                    Textarea::make('observacoes')->label('Observacoes')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('licitacao.nome')->label('Licitacao')->searchable()->sortable(),
                TextColumn::make('fornecedor.nome')->label('Fornecedor')->searchable()->sortable(),
                TextColumn::make('valor')->label('Valor')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->searchable()->sortable(),
                TextColumn::make('observacoes')->label('Observacoes')->searchable()->sortable(),
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
            'index' => ListPropostas::route('/'),
            'create' => CreateProposta::route('/create'),
            'view' => ViewProposta::route('/{record}'),
            'edit' => EditProposta::route('/{record}/edit'),
        ];
    }
}
