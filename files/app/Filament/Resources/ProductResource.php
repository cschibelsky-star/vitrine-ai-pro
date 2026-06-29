<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Produtos';
    protected static ?string $navigationGroup = 'Produtos e Licenças';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Produto';
    protected static ?string $pluralModelLabel = 'Produtos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Produto')->schema([
                Forms\Components\TextInput::make('nome')->label('Nome do Produto')->required()->maxLength(255),
                Forms\Components\Select::make('categoria')->label('Categoria')->options(['Mídia'=>'Mídia','Turismo'=>'Turismo','Saúde'=>'Saúde','Governo'=>'Governo','Educação'=>'Educação','Outros'=>'Outros'])->searchable(),
                Forms\Components\Select::make('status')->label('Status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo'])->default('Ativo')->required(),
                Forms\Components\Textarea::make('descricao')->label('Descrição')->rows(4)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nome')->label('Produto')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('categoria')->label('Categoria')->badge()->sortable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => $state === 'Ativo' ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('licenses_count')->label('Licenças')->counts('licenses'),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y')->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo']),
            Tables\Filters\SelectFilter::make('categoria')->options(['Mídia'=>'Mídia','Turismo'=>'Turismo','Saúde'=>'Saúde','Governo'=>'Governo']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListProducts::route('/'), 'create'=>Pages\CreateProduct::route('/create'), 'edit'=>Pages\EditProduct::route('/{record}/edit')];
    }
}
