<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\CompanyModule;
use App\Models\Contract;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Contratos / Propostas';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Contrato';
    protected static ?string $pluralModelLabel = 'Contratos / Propostas';

    protected static function calcularValoresAutomaticos(Forms\Set $set, ?int $planId, ?int $companyId = null): void
    {
        if (! $planId) {
            return;
        }

        $plan = Plan::find($planId);

        if (! $plan) {
            return;
        }

        $valorMensal = (float) ($plan->valor_mensal ?? 0);
        $valorImplantacao = (float) ($plan->valor_implantacao ?? 0);

        $valorModulosExtras = 0.0;

        if ($companyId) {
            $valorModulosExtras = (float) CompanyModule::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($planId) {
                    $query->where('plan_id', $planId)
                        ->orWhereNull('plan_id');
                })
                ->where('status', 'Ativo')
                ->whereIn('tipo_contratacao', ['extra', 'premium'])
                ->sum('valor_mensal_adicional');
        }

        $set('valor_implantacao', $valorImplantacao);
        $set('valor_mensal', $valorMensal);
        $set('valor_modulos_extras', $valorModulosExtras);
        $set('valor_total_mensal', $valorMensal + $valorModulosExtras);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cliente e Escopo')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Cliente')
                    ->relationship('company', 'nome')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state): void {
                        self::calcularValoresAutomaticos($set, $get('plan_id'), $state);
                    })
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->label('Produto')
                    ->relationship('product', 'nome')
                    ->searchable()
                    ->preload()
                    ->live(),
                Forms\Components\Select::make('plan_id')
                    ->label('Plano')
                    ->relationship('plan', 'nome')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state): void {
                        self::calcularValoresAutomaticos($set, $state, $get('company_id'));
                    }),
                Forms\Components\TextInput::make('numero')->label('Número / Código')->maxLength(100),
                Forms\Components\TextInput::make('titulo')->label('Título')->maxLength(255)->columnSpanFull(),
                Forms\Components\Select::make('tipo_contrato')->label('Tipo')->options([
                    'implantacao'=>'Implantação',
                    'mensalidade'=>'Mensalidade SaaS',
                    'implantacao_mensalidade'=>'Implantação + Mensalidade',
                    'modulos_extras'=>'Módulos Extras',
                    'governo'=>'Governo / Institucional',
                    'white_label'=>'White Label',
                ])->default('implantacao_mensalidade')->required(),
                Forms\Components\Select::make('status')->label('Status')->options([
                    'rascunho'=>'Rascunho',
                    'enviado'=>'Enviado',
                    'em_negociacao'=>'Em negociação',
                    'aprovado'=>'Aprovado',
                    'ativo'=>'Ativo',
                    'suspenso'=>'Suspenso',
                    'encerrado'=>'Encerrado',
                ])->default('rascunho')->required(),
            ])->columns(3),
            Forms\Components\Section::make('Valores')->description('Os valores são preenchidos automaticamente pelo plano selecionado e pelos módulos extras ativos do cliente. Podem ser ajustados manualmente em propostas especiais.')->schema([
                Forms\Components\TextInput::make('valor_implantacao')->label('Implantação')->numeric()->prefix('R$')->default(0),
                Forms\Components\TextInput::make('valor_mensal')->label('Mensalidade Base')->numeric()->prefix('R$')->default(0)->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                        $set('valor_total_mensal', (float) ($get('valor_mensal') ?? 0) + (float) ($get('valor_modulos_extras') ?? 0));
                    }),
                Forms\Components\TextInput::make('valor_modulos_extras')->label('Módulos Extras')->numeric()->prefix('R$')->default(0)->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                        $set('valor_total_mensal', (float) ($get('valor_mensal') ?? 0) + (float) ($get('valor_modulos_extras') ?? 0));
                    }),
                Forms\Components\TextInput::make('valor_total_mensal')->label('Total Mensal Previsto')->numeric()->prefix('R$')->default(0),
            ])->columns(4),
            Forms\Components\Section::make('Vigência e Documentos')->schema([
                Forms\Components\DatePicker::make('data_inicio')->label('Início')->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('data_fim')->label('Fim')->displayFormat('d/m/Y'),
                Forms\Components\TextInput::make('dia_vencimento')->label('Dia de Vencimento')->numeric()->minValue(1)->maxValue(31),
                Forms\Components\TextInput::make('link_proposta')->label('Link da Proposta')->url()->columnSpanFull(),
                Forms\Components\TextInput::make('link_contrato')->label('Link do Contrato')->url()->columnSpanFull(),
                Forms\Components\Textarea::make('observacoes')->label('Observações')->rows(4)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('plan.nome')->label('Plano')->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('tipo_contrato')->label('Tipo')->badge(),
            Tables\Columns\TextColumn::make('valor_implantacao')->label('Implantação')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('valor_total_mensal')->label('Total Mensal')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('dia_vencimento')->label('Venc.'),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                'ativo','aprovado' => 'success',
                'enviado','em_negociacao','rascunho' => 'warning',
                'suspenso' => 'danger',
                'encerrado' => 'gray',
                default => 'gray',
            }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'rascunho'=>'Rascunho','enviado'=>'Enviado','em_negociacao'=>'Em negociação','aprovado'=>'Aprovado','ativo'=>'Ativo','suspenso'=>'Suspenso','encerrado'=>'Encerrado'
            ]),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListContracts::route('/'), 'create'=>Pages\CreateContract::route('/create'), 'edit'=>Pages\EditContract::route('/{record}/edit')];
    }
}
