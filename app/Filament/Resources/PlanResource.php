<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Planos';
    protected static ?string $navigationGroup = 'Produtos e Licenças';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Plano';
    protected static ?string $pluralModelLabel = 'Planos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identificação')->columns(2)->schema([
                Forms\Components\Select::make('product_id')->label('Produto')->relationship('product', 'nome')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('nome')->label('Nome do Plano')->required()->maxLength(255),
            ]),
            Section::make('Valores e Cobrança')->columns(3)->schema([
                Forms\Components\TextInput::make('valor_mensal')->label('Valor Mensal (R$)')->numeric()->prefix('R$')->default(0.00)->required(),
                Forms\Components\TextInput::make('valor_implantacao')->label('Valor Implantação (R$)')->numeric()->prefix('R$')->default(0.00),
                Forms\Components\Select::make('ciclo_cobranca')->label('Ciclo de Cobrança')->options(['mensal'=>'Mensal','anual'=>'Anual','implantacao'=>'Implantação','trial'=>'Trial','cortesia'=>'Cortesia'])->default('mensal')->required(),
            ]),
            Section::make('Detalhes')->columns(1)->schema([
                Forms\Components\Textarea::make('descricao')->label('Descrição')->rows(3),
                Forms\Components\Textarea::make('recursos')->label('Recursos incluídos')->rows(5)->placeholder("Recurso 1
Recurso 2
Recurso 3")->helperText('Liste os recursos, um por linha.'),
                Forms\Components\Select::make('status')->label('Status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo'])->default('Ativo')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nome')->label('Plano')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('valor_mensal')->label('Valor Mensal')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('valor_implantacao')->label('Implantação')->money('BRL')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('ciclo_cobranca')->label('Ciclo')->badge()->color(fn (string $state): string => match ($state) { 'mensal'=>'info', 'anual'=>'success', 'trial'=>'warning', 'cortesia'=>'gray', 'implantacao'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'Ativo'=>'success', 'Inativo'=>'danger', default=>'gray' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('product_id')->label('Produto')->relationship('product', 'nome'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListPlans::route('/'), 'create'=>Pages\CreatePlan::route('/create'), 'edit'=>Pages\EditPlan::route('/{record}/edit')];
    }
}
