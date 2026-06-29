<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseResource\Pages;
use App\Models\License;
use App\Models\Plan;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Licenças';
    protected static ?string $navigationGroup = 'Produtos e Licenças';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Licenças';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Vínculo')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente / Empresa')->relationship('company', 'nome')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Produto')->options(Product::where('status', 'Ativo')->orderBy('nome')->pluck('nome', 'id'))->searchable()->required()->live()->afterStateUpdated(function (Set $set) { $set('plan_id', null); $set('plano', null); $set('valor', null); $set('status', null); $set('vencimento', null); }),
                Forms\Components\Select::make('plan_id')->label('Plano')->options(function (Get $get): Collection { $productId = $get('product_id'); if (! $productId) return collect(); return Plan::where('product_id', $productId)->where('status', 'Ativo')->orderBy('nome')->get()->mapWithKeys(fn (Plan $plan) => [$plan->id => "{$plan->nome} — R$ " . number_format((float) $plan->valor_mensal, 2, ',', '.')]); })->searchable()->live()->afterStateUpdated(function (Set $set, ?string $state) { if (! $state) return; $plan = Plan::find($state); if (! $plan) return; $set('valor', $plan->valor_mensal); $set('plano', $plan->nome); $set('status', $plan->statusLicencaSugerido()); $vencimento = $plan->calcularVencimento(now()); $set('vencimento', $vencimento?->format('Y-m-d')); })->required(),
                Forms\Components\Hidden::make('plano'),
            ]),
            Section::make('Dados da Licença')->columns(3)->schema([
                Forms\Components\TextInput::make('chave')->label('Chave / Token')->maxLength(255),
                Forms\Components\TextInput::make('valor')->label('Valor Cobrado (R$)')->numeric()->prefix('R$'),
                Forms\Components\Select::make('status')->label('Status')->options(['Ativa'=>'Ativa','Trial'=>'Trial','Homologação'=>'Homologação','Suspensa'=>'Suspensa','Cancelada'=>'Cancelada'])->required(),
            ]),
            Section::make('Vigência')->columns(2)->schema([
                Forms\Components\DatePicker::make('inicio')->label('Data de Início')->default(now())->required(),
                Forms\Components\DatePicker::make('vencimento')->label('Data de Vencimento'),
            ]),
            Section::make('Observações')->schema([Forms\Components\Textarea::make('observacoes')->label('Observações Internas')->rows(3)]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->sortable(),
            Tables\Columns\TextColumn::make('plan.nome')->label('Plano')->default(fn ($record) => $record->plano)->sortable(),
            Tables\Columns\TextColumn::make('valor')->label('Valor')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('inicio')->label('Início')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('vencimento')->label('Vencimento')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'Ativa'=>'success','Trial'=>'warning','Homologação'=>'info','Suspensa'=>'danger','Cancelada'=>'gray', default=>'gray' }),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['Ativa'=>'Ativa','Trial'=>'Trial','Homologação'=>'Homologação','Suspensa'=>'Suspensa','Cancelada'=>'Cancelada'])])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListLicenses::route('/'), 'create'=>Pages\CreateLicense::route('/create'), 'edit'=>Pages\EditLicense::route('/{record}/edit')];
    }
}
