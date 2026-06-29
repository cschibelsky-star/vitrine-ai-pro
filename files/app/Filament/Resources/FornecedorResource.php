<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FornecedorResource\Pages\CreateFornecedor;
use App\Filament\Resources\FornecedorResource\Pages\EditFornecedor;
use App\Filament\Resources\FornecedorResource\Pages\ListFornecedores;
use App\Filament\Resources\FornecedorResource\Pages\ViewFornecedor;
use App\Models\Fornecedor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedor::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Módulos Gerados';
    protected static ?string $navigationLabel = 'Fornecedores';
    protected static ?string $slug = 'fornecedores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    TextInput::make('nome')->label('Nome')->maxLength(255),
                    TextInput::make('documento')->label('Documento')->maxLength(255),
                    TextInput::make('email')->label('Email')->maxLength(255),
                    TextInput::make('telefone')->label('Telefone')->maxLength(255),
                    TextInput::make('cidade')->label('Cidade')->maxLength(255),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('documento')->label('Documento')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('telefone')->label('Telefone')->searchable()->sortable(),
                TextColumn::make('cidade')->label('Cidade')->searchable()->sortable(),
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
            'index' => ListFornecedores::route('/'),
            'create' => CreateFornecedor::route('/create'),
            'view' => ViewFornecedor::route('/{record}'),
            'edit' => EditFornecedor::route('/{record}/edit'),
        ];
    }
}
