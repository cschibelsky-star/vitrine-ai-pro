<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Módulos';
    protected static ?string $navigationGroup = 'Centro Operacional';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'Módulo';
    protected static ?string $pluralModelLabel = 'Módulos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identificação')->columns(2)->schema([
                Forms\Components\Select::make('product_id')->label('Produto / Vertical')->relationship('product', 'nome')->searchable()->preload(),
                Forms\Components\TextInput::make('nome')->label('Nome do Módulo')->required()->maxLength(255),
                Forms\Components\TextInput::make('codigo')->label('Código interno')->required()->unique(ignoreRecord: true)->maxLength(255)->helperText('Ex.: tv-rss, guia-eventos, governo-ia-atendimento.'),
                Forms\Components\TextInput::make('categoria')->label('Categoria')->maxLength(255)->placeholder('TV Digital, Guia Digital, Governo, SISMED, Interno'),
            ]),
            Section::make('Classificação Comercial')->columns(4)->schema([
                Forms\Components\Select::make('tipo')->label('Tipo')->options([
                    'incluido' => 'Incluído',
                    'extra' => 'Extra',
                    'premium' => 'Premium',
                    'interno' => 'Interno',
                    'futuro' => 'Futuro',
                ])->default('incluido')->required(),
                Forms\Components\TextInput::make('valor_adicional')->label('Valor adicional')->numeric()->prefix('R$')->default(0),
                Forms\Components\Select::make('status')->label('Status')->options([
                    'Ativo' => 'Ativo',
                    'Inativo' => 'Inativo',
                    'Implantação' => 'Implantação',
                    'Bloqueado' => 'Bloqueado',
                    'Futuro' => 'Futuro',
                ])->default('Ativo')->required(),
                Forms\Components\TextInput::make('ordem')->label('Ordem')->numeric()->default(0),
            ]),
            Section::make('Descrição')->schema([
                Forms\Components\Textarea::make('descricao')->label('Descrição operacional')->rows(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nome')->label('Módulo')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('codigo')->label('Código')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('categoria')->label('Categoria')->badge()->sortable()->searchable(),
            Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()->color(fn (?string $state): string => match ($state) {
                'incluido' => 'success',
                'extra' => 'warning',
                'premium' => 'danger',
                'interno' => 'info',
                'futuro' => 'gray',
                default => 'gray',
            }),
            Tables\Columns\TextColumn::make('valor_adicional')->label('Valor Extra')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (?string $state): string => match ($state) {
                'Ativo' => 'success',
                'Implantação' => 'warning',
                'Futuro' => 'gray',
                'Bloqueado' => 'danger',
                'Inativo' => 'danger',
                default => 'gray',
            }),
        ])->defaultSort('ordem')->filters([
            Tables\Filters\SelectFilter::make('product_id')->label('Produto')->relationship('product', 'nome'),
            Tables\Filters\SelectFilter::make('tipo')->options(['incluido'=>'Incluído','extra'=>'Extra','premium'=>'Premium','interno'=>'Interno','futuro'=>'Futuro']),
            Tables\Filters\SelectFilter::make('status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo','Implantação'=>'Implantação','Bloqueado'=>'Bloqueado','Futuro'=>'Futuro']),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }
}
