<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Cobranças';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Cobrança';
    protected static ?string $pluralModelLabel = 'Cobranças';

    /**
     * Retorna contratos com label sempre em string.
     * Corrige erro 500 do Filament quando algum contrato está sem título.
     */
    protected static function contractOptions(): Collection
    {
        return Contract::query()
            ->with(['company', 'product', 'plan'])
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (Contract $contract): array {
                $cliente = $contract->company?->nome ?: 'Cliente não informado';
                $produto = $contract->product?->nome ?: 'Produto não informado';
                $plano = $contract->plan?->nome ?: 'Plano não informado';
                $titulo = $contract->titulo ?: trim("{$cliente} / {$produto} / {$plano}");
                $numero = $contract->numero ? "{$contract->numero} — " : '';

                return [$contract->id => (string) ($numero . $titulo)];
            });
    }

    protected static function preencherContrato(Forms\Set $set, ?int $contractId, ?string $tipoCobranca = null): void
    {
        if (! $contractId) {
            return;
        }

        $contract = Contract::with(['company', 'product', 'plan'])->find($contractId);

        if (! $contract) {
            return;
        }

        $tipoCobranca = $tipoCobranca ?: 'mensalidade';

        $set('company_id', $contract->company_id);
        $set('product_id', $contract->product_id);
        $set('plan_id', $contract->plan_id);

        $valor = match ($tipoCobranca) {
            'implantacao' => (float) ($contract->valor_implantacao ?? 0),
            'modulo_extra' => (float) ($contract->valor_modulos_extras ?? 0),
            default => (float) ($contract->valor_total_mensal ?? $contract->valor_mensal ?? 0),
        };

        $set('valor', $valor);

        $cliente = $contract->company?->nome ?: 'Cliente não informado';
        $produto = $contract->product?->nome ?: 'Produto não informado';
        $plano = $contract->plan?->nome ?: 'Plano não informado';
        $titulo = $contract->titulo ?: "{$cliente} / {$produto} / {$plano}";

        $descricao = match ($tipoCobranca) {
            'implantacao' => 'Implantação - '.$titulo,
            'modulo_extra' => 'Módulos extras - '.$titulo,
            default => 'Mensalidade - '.$titulo,
        };

        $set('descricao', $descricao);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cliente e Contrato')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Cliente')
                    ->options(fn (): Collection => Company::query()->orderBy('nome')->get()->mapWithKeys(fn (Company $company) => [
                        $company->id => (string) ($company->nome ?: 'Cliente #'.$company->id),
                    ]))
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('contract_id')
                    ->label('Contrato / Proposta')
                    ->options(fn (): Collection => self::contractOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state): void {
                        self::preencherContrato($set, $state, $get('tipo_cobranca'));
                    }),

                Forms\Components\Select::make('product_id')
                    ->label('Produto')
                    ->options(fn (): Collection => Product::query()->orderBy('nome')->get()->mapWithKeys(fn (Product $product) => [
                        $product->id => (string) ($product->nome ?: 'Produto #'.$product->id),
                    ]))
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('plan_id')
                    ->label('Plano')
                    ->options(fn (): Collection => Plan::query()->orderBy('nome')->get()->mapWithKeys(fn (Plan $plan) => [
                        $plan->id => (string) ($plan->nome ?: 'Plano #'.$plan->id),
                    ]))
                    ->searchable()
                    ->preload(),
            ])->columns(2),

            Forms\Components\Section::make('Dados da Cobrança')
                ->description('Ao selecionar um contrato, o sistema preenche cliente, produto, plano, descrição e valor conforme o tipo da cobrança.')
                ->schema([
                    Forms\Components\Select::make('tipo_cobranca')
                        ->label('Tipo')
                        ->options([
                            'implantacao' => 'Implantação',
                            'mensalidade' => 'Mensalidade',
                            'modulo_extra' => 'Módulo Extra',
                            'ajuste' => 'Ajuste',
                            'outro' => 'Outro',
                        ])
                        ->default('mensalidade')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                            self::preencherContrato($set, $get('contract_id'), $state);
                        })
                        ->required(),

                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('valor')
                        ->label('Valor')
                        ->numeric()
                        ->prefix('R$')
                        ->required(),

                    Forms\Components\DatePicker::make('competencia')
                        ->label('Competência')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('vencimento')
                        ->label('Vencimento')
                        ->displayFormat('d/m/Y')
                        ->required(),

                    Forms\Components\DatePicker::make('data_pagamento')
                        ->label('Data do Pagamento')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'Aberto' => 'Aberto',
                            'Pago' => 'Pago',
                            'Atrasado' => 'Atrasado',
                            'Cancelado' => 'Cancelado',
                            'Suspenso' => 'Suspenso',
                        ])
                        ->default('Aberto')
                        ->required(),

                    Forms\Components\Select::make('forma_pagamento')
                        ->label('Forma de Pagamento')
                        ->options([
                            'pix' => 'PIX',
                            'boleto' => 'Boleto',
                            'cartao' => 'Cartão',
                            'transferencia' => 'Transferência',
                            'dinheiro' => 'Dinheiro',
                            'outro' => 'Outro',
                        ]),

                    Forms\Components\TextInput::make('link_pagamento')
                        ->label('Link de Pagamento')
                        ->url()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('referencia_externa')
                        ->label('Referência Externa')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('asaas_id')
                        ->label('ID Asaas')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('observacao')
                        ->label('Observação')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('tipo_cobranca')->label('Tipo')->badge()->sortable(),
            Tables\Columns\TextColumn::make('descricao')->label('Descrição')->limit(35)->searchable(),
            Tables\Columns\TextColumn::make('valor')->label('Valor')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('competencia')->label('Competência')->date('m/Y')->sortable(),
            Tables\Columns\TextColumn::make('vencimento')->label('Vencimento')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('data_pagamento')->label('Pagamento')->date('d/m/Y')->toggleable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                'Pago' => 'success',
                'Aberto' => 'warning',
                'Atrasado' => 'danger',
                'Cancelado', 'Suspenso' => 'gray',
                default => 'gray',
            }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['Aberto' => 'Aberto', 'Pago' => 'Pago', 'Atrasado' => 'Atrasado', 'Cancelado' => 'Cancelado', 'Suspenso' => 'Suspenso']),
            Tables\Filters\SelectFilter::make('tipo_cobranca')->options(['implantacao' => 'Implantação', 'mensalidade' => 'Mensalidade', 'modulo_extra' => 'Módulo Extra', 'ajuste' => 'Ajuste', 'outro' => 'Outro']),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
