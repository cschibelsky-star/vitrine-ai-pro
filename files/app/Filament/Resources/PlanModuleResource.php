<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanModuleResource\Pages;
use App\Models\PlanModule;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanModuleResource extends Resource
{
    protected static ?string $model = PlanModule::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Módulos por Plano';
    protected static ?string $navigationGroup = 'Produtos e Licenças';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Módulo por Plano';
    protected static ?string $pluralModelLabel = 'Módulos por Plano';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Vínculo Plano x Módulo')->columns(2)->schema([
                Forms\Components\Select::make('plan_id')->label('Plano')->relationship('plan', 'nome')->searchable()->preload()->required(),
                Forms\Components\Select::make('module_id')->label('Módulo')->relationship('module', 'nome')->searchable()->preload()->required(),
            ]),
            Section::make('Regra Comercial')->columns(4)->schema([
                Forms\Components\Select::make('tipo_inclusao')->label('Inclusão')->options([
                    'incluido' => 'Incluído no plano',
                    'extra' => 'Extra pago',
                    'premium' => 'Premium',
                    'bloqueado' => 'Bloqueado',
                ])->default('incluido')->required(),
                Forms\Components\TextInput::make('valor_adicional')->label('Valor adicional')->numeric()->prefix('R$')->default(0),
                Forms\Components\TextInput::make('limite_uso')->label('Limite / Franquia')->placeholder('Ex.: 10 vídeos/mês'),
                Forms\Components\Select::make('status')->label('Status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo'])->default('Ativo')->required(),
            ]),
            Section::make('Observações')->schema([
                Forms\Components\Textarea::make('observacoes')->label('Observações')->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('plan.product.nome')->label('Produto')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('plan.nome')->label('Plano')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('module.nome')->label('Módulo')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('tipo_inclusao')->label('Tipo')->badge()->color(fn (?string $state): string => match ($state) {
                'incluido' => 'success',
                'extra' => 'warning',
                'premium' => 'danger',
                'bloqueado' => 'gray',
                default => 'gray',
            }),
            Tables\Columns\TextColumn::make('valor_adicional')->label('Valor Extra')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('limite_uso')->label('Limite')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (?string $state): string => $state === 'Ativo' ? 'success' : 'danger'),
        ])->filters([
            Tables\Filters\SelectFilter::make('plan_id')->label('Plano')->relationship('plan', 'nome'),
            Tables\Filters\SelectFilter::make('tipo_inclusao')->options(['incluido'=>'Incluído','extra'=>'Extra','premium'=>'Premium','bloqueado'=>'Bloqueado']),
            Tables\Filters\SelectFilter::make('status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo']),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanModules::route('/'),
            'create' => Pages\CreatePlanModule::route('/create'),
            'edit' => Pages\EditPlanModule::route('/{record}/edit'),
        ];
    }
}
