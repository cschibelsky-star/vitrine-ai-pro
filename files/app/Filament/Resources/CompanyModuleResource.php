<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyModuleResource\Pages;
use App\Models\CompanyModule;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyModuleResource extends Resource
{
    protected static ?string $model = CompanyModule::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Módulos por Cliente';
    protected static ?string $navigationGroup = 'Centro Operacional';
    protected static ?int $navigationSort = 11;
    protected static ?string $modelLabel = 'Módulo por Cliente';
    protected static ?string $pluralModelLabel = 'Módulos por Cliente';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Cliente e Módulo')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente / Empresa')->relationship('company', 'nome')->searchable()->preload()->required(),
                Forms\Components\Select::make('module_id')->label('Módulo')->relationship('module', 'nome')->searchable()->preload()->required(),
                Forms\Components\Select::make('plan_id')->label('Plano de referência')->relationship('plan', 'nome')->searchable()->preload(),
            ]),
            Section::make('Contratação')->columns(4)->schema([
                Forms\Components\Select::make('tipo_contratacao')->label('Tipo')->options([
                    'plano' => 'Incluído no plano',
                    'extra' => 'Extra pago',
                    'premium' => 'Premium',
                    'cortesia' => 'Cortesia',
                    'implantacao' => 'Implantação',
                    'bloqueado' => 'Bloqueado',
                ])->default('plano')->required(),
                Forms\Components\TextInput::make('valor_mensal_adicional')->label('Valor mensal adicional')->numeric()->prefix('R$')->default(0),
                Forms\Components\Select::make('status')->label('Status')->options([
                    'Ativo' => 'Ativo',
                    'Implantação' => 'Implantação',
                    'Bloqueado' => 'Bloqueado',
                    'Suspenso' => 'Suspenso',
                    'Futuro' => 'Futuro',
                ])->default('Ativo')->required(),
            ]),
            Section::make('Vigência')->columns(2)->schema([
                Forms\Components\DatePicker::make('data_inicio')->label('Data de início'),
                Forms\Components\DatePicker::make('data_fim')->label('Data final'),
            ]),
            Section::make('Observações')->schema([
                Forms\Components\Textarea::make('observacoes')->label('Observações')->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('module.product.nome')->label('Produto')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('module.nome')->label('Módulo')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('plan.nome')->label('Plano')->sortable()->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('tipo_contratacao')->label('Tipo')->badge()->color(fn (?string $state): string => match ($state) {
                'plano' => 'success',
                'extra' => 'warning',
                'premium' => 'danger',
                'cortesia' => 'info',
                'implantacao' => 'warning',
                'bloqueado' => 'gray',
                default => 'gray',
            }),
            Tables\Columns\TextColumn::make('valor_mensal_adicional')->label('Valor Extra')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (?string $state): string => match ($state) {
                'Ativo' => 'success',
                'Implantação' => 'warning',
                'Futuro' => 'gray',
                'Bloqueado' => 'danger',
                'Suspenso' => 'danger',
                default => 'gray',
            }),
        ])->filters([
            Tables\Filters\SelectFilter::make('company_id')->label('Cliente')->relationship('company', 'nome'),
            Tables\Filters\SelectFilter::make('module_id')->label('Módulo')->relationship('module', 'nome'),
            Tables\Filters\SelectFilter::make('status')->options(['Ativo'=>'Ativo','Implantação'=>'Implantação','Bloqueado'=>'Bloqueado','Suspenso'=>'Suspenso','Futuro'=>'Futuro']),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyModules::route('/'),
            'create' => Pages\CreateCompanyModule::route('/create'),
            'edit' => Pages\EditCompanyModule::route('/{record}/edit'),
        ];
    }
}
