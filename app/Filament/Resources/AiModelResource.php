<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiModelResource\Pages;
use App\Models\AiModel;
use App\Models\AiProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiModelResource extends Resource
{
    protected static ?string $model = AiModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Modelos IA';
    protected static ?string $navigationGroup = '07 · IA Center';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Modelo IA';
    protected static ?string $pluralModelLabel = 'Modelos IA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identidade do modelo')->columns(2)->schema([
                Forms\Components\Select::make('ai_provider_id')
                    ->label('Provedor')
                    ->options(fn () => AiProvider::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?AiModel $record): bool => (bool) $record?->is_verified)
                    ->dehydrated(true),
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(160),
                Forms\Components\TextInput::make('provider_model_id')
                    ->label('ID no provedor')
                    ->required()
                    ->maxLength(190)
                    ->disabled(fn (?AiModel $record): bool => (bool) $record?->is_verified)
                    ->dehydrated(true),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(190)
                    ->disabled(fn (?AiModel $record): bool => (bool) $record?->is_verified)
                    ->dehydrated(true),
                Forms\Components\Select::make('modality')->label('Modalidade')->options([
                    'text' => 'Texto',
                    'embedding' => 'Embedding',
                    'image' => 'Imagem',
                    'audio' => 'Áudio',
                    'transcription' => 'Transcrição',
                ])->default('text')->required(),
                Forms\Components\Select::make('tier')->label('Faixa')->options([
                    'economy' => 'Econômico',
                    'balanced' => 'Equilibrado',
                    'premium' => 'Premium',
                ])->default('balanced')->required(),
                Forms\Components\TextInput::make('context_window')->label('Janela de contexto')->numeric(),
                Forms\Components\TagsInput::make('capabilities')->label('Capacidades')->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Cobrança e operação')->columns(2)->schema([
                Forms\Components\Select::make('billing_unit')->label('Unidade de cobrança')->options([
                    'tokens' => 'Tokens',
                    'image' => 'Por imagem',
                    'minute' => 'Por minuto',
                    'request' => 'Por requisição',
                ])->default('tokens')->required(),
                Forms\Components\TextInput::make('input_cost_per_million')->label('Entrada / 1M tokens')->numeric()->prefix('R$')->default(0),
                Forms\Components\TextInput::make('output_cost_per_million')->label('Saída / 1M tokens')->numeric()->prefix('R$')->default(0),
                Forms\Components\TextInput::make('unit_cost_brl')->label('Custo por unidade')->numeric()->prefix('R$')->default(0),
                Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true),
                Forms\Components\Toggle::make('is_experimental')->label('Experimental')->default(true),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Homologado/verificado')
                    ->helperText('Somente as ações Homologar ou Revogar homologação podem alterar este estado.')
                    ->disabled()
                    ->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Modelo')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('provider_model_id')->label('ID Provider')->searchable()->limit(30)->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('provider.name')->label('Provedor')->badge()->sortable(),
            Tables\Columns\TextColumn::make('modality')->label('Tipo')->badge(),
            Tables\Columns\TextColumn::make('tier')->label('Faixa')->badge()->color(fn (string $state): string => match ($state) {
                'economy' => 'success',
                'balanced' => 'warning',
                'premium' => 'danger',
                default => 'gray',
            }),
            Tables\Columns\TextColumn::make('billing_unit')->label('Cobrança')->badge(),
            Tables\Columns\TextColumn::make('context_window')->label('Contexto')->numeric()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('input_cost_per_million')->label('Entrada/1M')->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('output_cost_per_million')->label('Saída/1M')->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('unit_cost_brl')->label('Custo/unidade')->money('BRL')->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            Tables\Columns\IconColumn::make('is_verified')->label('Homologado')->boolean(),
            Tables\Columns\IconColumn::make('is_experimental')->label('Experimental')->boolean()->toggleable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('ai_provider_id')->label('Provedor')->options(fn () => AiProvider::query()->pluck('name', 'id')),
            Tables\Filters\SelectFilter::make('tier')->label('Faixa')->options(['economy'=>'Econômico','balanced'=>'Equilibrado','premium'=>'Premium']),
            Tables\Filters\SelectFilter::make('modality')->label('Modalidade')->options(['text'=>'Texto','embedding'=>'Embedding','image'=>'Imagem','audio'=>'Áudio','transcription'=>'Transcrição']),
            Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
            Tables\Filters\TernaryFilter::make('is_verified')->label('Homologado'),
        ])->actions([
            Tables\Actions\Action::make('homologar')
                ->label('Homologar')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Homologar modelo de IA')
                ->modalDescription('Após homologado, este modelo poderá ser selecionado pelo AI Hub e pelo Smart Router. A ação não executa chamada externa.')
                ->visible(fn (AiModel $record): bool => ! (bool) $record->is_verified && (bool) $record->is_active)
                ->action(function (AiModel $record): void {
                    $record->forceFill(['is_verified' => true])->save();

                    Notification::make()
                        ->title('Modelo homologado')
                        ->body($record->provider_model_id . ' está liberado para uso pelo AI Hub.')
                        ->success()
                        ->send();
                }),
            Tables\Actions\Action::make('revogarHomologacao')
                ->label('Revogar')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Revogar homologação')
                ->modalDescription('O modelo deixará imediatamente de ser elegível no AI Hub e no Smart Router. Nenhum registro de consumo será removido.')
                ->visible(fn (AiModel $record): bool => (bool) $record->is_verified)
                ->action(function (AiModel $record): void {
                    $record->forceFill(['is_verified' => false])->save();

                    Notification::make()
                        ->title('Homologação revogada')
                        ->body($record->provider_model_id . ' não será mais selecionado pelo AI Hub.')
                        ->warning()
                        ->send();
                }),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiModels::route('/'),
            'create' => Pages\CreateAiModel::route('/create'),
            'edit' => Pages\EditAiModel::route('/{record}/edit'),
        ];
    }
}
