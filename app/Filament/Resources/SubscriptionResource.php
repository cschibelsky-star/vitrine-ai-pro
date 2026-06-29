<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Company;
use App\Models\License;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Assinaturas';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assinatura')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente / Empresa')->options(fn() => Company::query()->orderBy('nome')->pluck('nome','id'))->searchable()->preload()->required(),
                Forms\Components\Select::make('plan_id')->label('Plano')->options(fn() => Plan::query()->orderBy('nome')->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('license_id')->label('Licença')->options(fn() => License::query()->orderByDesc('id')->pluck('chave','id'))->searchable()->preload(),
                Forms\Components\TextInput::make('asaas_subscription_id')->label('ID Assinatura Asaas'),
                Forms\Components\TextInput::make('asaas_customer_id')->label('ID Cliente Asaas'),
                Forms\Components\TextInput::make('external_reference')->label('Referência Externa'),
                Forms\Components\Select::make('status')->options(['Pendente'=>'Pendente','Ativa'=>'Ativa','Suspensa'=>'Suspensa','Cancelada'=>'Cancelada'])->default('Pendente')->required(),
                Forms\Components\TextInput::make('billing_cycle')->label('Ciclo')->placeholder('MONTHLY'),
                Forms\Components\TextInput::make('value')->label('Valor')->numeric()->prefix('R$')->default(0),
                Forms\Components\DatePicker::make('next_due_date')->label('Próximo vencimento'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('plan.nome')->label('Plano')->sortable(),
            Tables\Columns\TextColumn::make('value')->label('Valor')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('next_due_date')->label('Próx. venc.')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('asaas_subscription_id')->label('Asaas')->toggleable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
