<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationLabel = 'Comercial';
    protected static ?string $navigationGroup = 'Comercial';
    protected static ?string $modelLabel = 'Lead';
    protected static ?string $pluralModelLabel = 'Comercial';

    public static function produtoOptions(): array
    {
        return [
            'TV Digital Enterprise' => 'TV Digital Enterprise',
            'Portal News AI' => 'Portal News AI',
            'Guia Digital da Cidade' => 'Guia Digital da Cidade',
            'Visite Cidade' => 'Visite Cidade',
            'SISMED' => 'SISMED',
            'Município Digital IA' => 'Município Digital IA',
            'TV Digital White Label' => 'TV Digital White Label',
            'Marketing IA' => 'Marketing IA',
            'Sob análise' => 'Sob análise',
            'Outro' => 'Outro',
        ];
    }

    public static function planosPorProduto(?string $produto): array
    {
        return match ($produto) {
            'TV Digital Enterprise' => [
                'Start' => 'Start — R$ 497,00',
                'Pro' => 'Pro — R$ 997,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'White Label' => 'White Label — R$ 2.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'Portal News AI' => [
                'Start' => 'Start — R$ 497,00',
                'Pro' => 'Pro — R$ 997,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'Guia Digital da Cidade', 'Visite Cidade' => [
                'Beta' => 'Beta — R$ 0,00',
                'Start' => 'Start — R$ 497,00',
                'Pro' => 'Pro — R$ 997,00',
                'Governo' => 'Governo — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'SISMED' => [
                'Trial' => 'Trial — R$ 0,00',
                'Implantação' => 'Implantação — R$ 0,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'Município Digital IA' => [
                'Implantação' => 'Implantação — R$ 0,00',
                'Governo' => 'Governo — R$ 1.500,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'TV Digital White Label' => [
                'White Label' => 'White Label — R$ 2.500,00',
                'Agência' => 'Agência — R$ 3.500,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'Marketing IA' => [
                'Start' => 'Start — R$ 497,00',
                'Pro' => 'Pro — R$ 997,00',
                'Enterprise' => 'Enterprise — R$ 1.500,00',
                'Sob proposta' => 'Sob proposta',
            ],
            'Sob análise', 'Outro' => [
                'Sob proposta' => 'Sob proposta',
            ],
            default => [],
        };
    }

    public static function valorPorPlano(?string $plano): ?float
    {
        return match ($plano) {
            'Beta', 'Trial', 'Implantação' => 0.00,
            'Start' => 497.00,
            'Pro' => 997.00,
            'Enterprise', 'Governo' => 1500.00,
            'White Label' => 2500.00,
            'Agência' => 3500.00,
            'Sob proposta' => null,
            default => null,
        };
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Lead')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('empresa')
                        ->label('Empresa')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('contato')
                        ->label('Nome do contato')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefone')
                        ->label('Telefone')
                        ->tel()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cidade')
                        ->label('Cidade')
                        ->maxLength(255)
                        ->visible(fn () => Schema::hasColumn('leads', 'cidade')),

                    Forms\Components\TextInput::make('estado')
                        ->label('Estado')
                        ->maxLength(2)
                        ->visible(fn () => Schema::hasColumn('leads', 'estado')),

                    Forms\Components\Select::make('produto_interesse')
                        ->label('Produto de interesse')
                        ->options(self::produtoOptions())
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('plano_sugerido', null);
                            $set('valor_estimado', null);
                        }),

                    Forms\Components\Select::make('origem_lead')
                        ->label('Origem do lead')
                        ->options(Lead::origemOptions())
                        ->searchable()
                        ->native(false),
                ]),

            Forms\Components\Section::make('Rastreabilidade da captação')
                ->description('Informações registradas automaticamente pelo formulário de origem.')
                ->columns(2)
                ->collapsed()
                ->visible(fn () => Schema::hasColumn('leads', 'pagina_origem'))
                ->schema([
                    Forms\Components\TextInput::make('external_id')
                        ->label('Identificador externo')
                        ->disabled(),

                    Forms\Components\TextInput::make('pagina_origem')
                        ->label('Página de origem')
                        ->disabled(),

                    Forms\Components\TextInput::make('campanha')
                        ->label('Campanha')
                        ->disabled(),

                    Forms\Components\Toggle::make('consentimento_lgpd')
                        ->label('Consentimento LGPD')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('capturado_em')
                        ->label('Capturado em')
                        ->seconds(false)
                        ->disabled(),

                    Forms\Components\KeyValue::make('metadata')
                        ->label('Metadados técnicos')
                        ->disabled()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Qualificação Comercial')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('plano_sugerido')
                        ->label('Plano sugerido')
                        ->options(fn (Get $get): array => self::planosPorProduto($get('produto_interesse')))
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->disabled(fn (Get $get): bool => ! filled($get('produto_interesse')))
                        ->placeholder(fn (Get $get): string => filled($get('produto_interesse')) ? 'Selecione o plano' : 'Selecione o produto primeiro')
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $set('valor_estimado', self::valorPorPlano($state));
                        }),

                    Forms\Components\TextInput::make('valor_estimado')
                        ->label('Valor estimado')
                        ->numeric()
                        ->prefix('R$')
                        ->step('0.01')
                        ->helperText('Preenchido automaticamente pelo plano, mas pode ser ajustado manualmente.'),

                    Forms\Components\Select::make('status_negociacao')
                        ->label('Status da negociação')
                        ->options(Lead::statusNegociacaoOptions())
                        ->default('Novo')
                        ->native(false),

                    Forms\Components\TextInput::make('responsavel_comercial')
                        ->label('Responsável comercial')
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Próxima Ação')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('proxima_acao')
                        ->label('Próxima ação')
                        ->options(Lead::proximaAcaoOptions())
                        ->searchable()
                        ->native(false),

                    Forms\Components\DatePicker::make('data_proxima_acao')
                        ->label('Data da próxima ação')
                        ->native(false),

                    Forms\Components\TextInput::make('link_proposta')
                        ->label('Link da proposta')
                        ->url()
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Observações')
                ->columns(1)
                ->schema([
                    Forms\Components\Textarea::make('observacoes')
                        ->label('Observações')
                        ->rows(3),

                    Forms\Components\Textarea::make('observacoes_internas')
                        ->label('Observações internas')
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contato')
                    ->label('Contato')
                    ->searchable(),

                Tables\Columns\TextColumn::make('produto_interesse')
                    ->label('Produto')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('plano_sugerido')
                    ->label('Plano')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valor_estimado')
                    ->label('Valor estimado')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_negociacao')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Novo' => 'gray',
                        'Contato' => 'info',
                        'Diagnóstico' => 'warning',
                        'Demonstração' => 'primary',
                        'Proposta' => 'info',
                        'Negociação' => 'warning',
                        'Fechado' => 'success',
                        'Perdido' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('origem_lead')
                    ->label('Origem')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pagina_origem')
                    ->label('Página')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Schema::hasColumn('leads', 'pagina_origem')),

                Tables\Columns\TextColumn::make('campanha')
                    ->label('Campanha')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Schema::hasColumn('leads', 'campanha')),

                Tables\Columns\IconColumn::make('consentimento_lgpd')
                    ->label('LGPD')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Schema::hasColumn('leads', 'consentimento_lgpd')),

                Tables\Columns\TextColumn::make('capturado_em')
                    ->label('Capturado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Schema::hasColumn('leads', 'capturado_em')),

                Tables\Columns\TextColumn::make('proxima_acao')
                    ->label('Próxima ação')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data_proxima_acao')
                    ->label('Data ação')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('responsavel_comercial')
                    ->label('Responsável')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_negociacao')
                    ->label('Status da negociação')
                    ->options(Lead::statusNegociacaoOptions()),

                Tables\Filters\SelectFilter::make('produto_interesse')
                    ->label('Produto de interesse')
                    ->options(self::produtoOptions()),

                Tables\Filters\SelectFilter::make('plano_sugerido')
                    ->label('Plano sugerido')
                    ->options([
                        'Beta' => 'Beta',
                        'Trial' => 'Trial',
                        'Implantação' => 'Implantação',
                        'Start' => 'Start',
                        'Pro' => 'Pro',
                        'Enterprise' => 'Enterprise',
                        'Governo' => 'Governo',
                        'White Label' => 'White Label',
                        'Agência' => 'Agência',
                        'Sob proposta' => 'Sob proposta',
                    ]),

                Tables\Filters\SelectFilter::make('origem_lead')
                    ->label('Origem do lead')
                    ->options(Lead::origemOptions()),

                Tables\Filters\SelectFilter::make('pagina_origem')
                    ->label('Página de origem')
                    ->options(fn () => Schema::hasColumn('leads', 'pagina_origem')
                        ? Lead::query()
                            ->whereNotNull('pagina_origem')
                            ->distinct()
                            ->orderBy('pagina_origem')
                            ->pluck('pagina_origem', 'pagina_origem')
                            ->toArray()
                        : [])
                    ->visible(fn () => Schema::hasColumn('leads', 'pagina_origem')),

                Tables\Filters\SelectFilter::make('responsavel_comercial')
                    ->label('Responsável comercial')
                    ->options(fn () => Lead::query()
                        ->whereNotNull('responsavel_comercial')
                        ->distinct()
                        ->pluck('responsavel_comercial', 'responsavel_comercial')
                        ->toArray()),

                Tables\Filters\Filter::make('data_proxima_acao')
                    ->form([
                        Forms\Components\DatePicker::make('de')->label('De'),
                        Forms\Components\DatePicker::make('ate')->label('Até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['de'] ?? null, fn ($q, $d) => $q->whereDate('data_proxima_acao', '>=', $d))
                            ->when($data['ate'] ?? null, fn ($q, $d) => $q->whereDate('data_proxima_acao', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('marcarProposta')
                        ->label('Marcar como Proposta')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->action(fn (Lead $record) => self::atualizarStatus($record, 'Proposta')),

                    Tables\Actions\Action::make('marcarNegociacao')
                        ->label('Marcar como Negociação')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('warning')
                        ->action(fn (Lead $record) => self::atualizarStatus($record, 'Negociação')),

                    Tables\Actions\Action::make('marcarFechado')
                        ->label('Marcar como Fechado')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Lead $record) => self::atualizarStatus($record, 'Fechado')),

                    Tables\Actions\Action::make('marcarPerdido')
                        ->label('Marcar como Perdido')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Lead $record) => self::atualizarStatus($record, 'Perdido')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function atualizarStatus(Lead $record, string $status): void
    {
        $record->update(['status_negociacao' => $status]);

        Notification::make()
            ->title("Status atualizado para: {$status}")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
