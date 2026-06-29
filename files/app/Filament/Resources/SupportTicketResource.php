<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Module;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Centro Operacional';

    protected static ?string $navigationLabel = 'Chamados';

    protected static ?string $modelLabel = 'Chamado';

    protected static ?string $pluralModelLabel = 'Chamados';

    protected static ?int $navigationSort = 10;

    protected static function optionLabel($record): string
    {
        return (string) (
            ($record->name ?? null)
            ?: ($record->nome ?? null)
            ?: ($record->company_name ?? null)
            ?: ($record->razao_social ?? null)
            ?: ($record->title ?? null)
            ?: ('Registro #' . ($record->id ?? ''))
        );
    }

    protected static function optionsFor(string $model): array
    {
        if (! class_exists($model)) {
            return [];
        }

        return $model::query()
            ->get()
            ->mapWithKeys(fn ($record) => [$record->id => self::optionLabel($record)])
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Chamado')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Cliente / Empresa')
                            ->options(fn () => self::optionsFor(Company::class))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Usuário solicitante')
                            ->options(fn () => self::optionsFor(User::class))
                            ->searchable(),

                        Forms\Components\Select::make('product_id')
                            ->label('Produto')
                            ->options(fn () => self::optionsFor(Product::class))
                            ->searchable(),

                        Forms\Components\Select::make('module_id')
                            ->label('Módulo relacionado')
                            ->options(fn () => self::optionsFor(Module::class))
                            ->searchable(),

                        Forms\Components\Select::make('contract_id')
                            ->label('Contrato relacionado')
                            ->options(fn () => self::optionsFor(Contract::class))
                            ->searchable(),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição do cliente')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options([
                                'Baixa' => 'Baixa',
                                'Média' => 'Média',
                                'Alta' => 'Alta',
                                'Urgente' => 'Urgente',
                            ])
                            ->default('Média')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Aberto' => 'Aberto',
                                'Em análise' => 'Em análise',
                                'Em execução' => 'Em execução',
                                'Aguardando cliente' => 'Aguardando cliente',
                                'Resolvido' => 'Resolvido',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->default('Aberto')
                            ->required(),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Resposta / observações internas')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('client_response')
                            ->label('Resposta ao cliente')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Aberto em'),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Resolvido em'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('company.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('priority')->label('Prioridade')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Abertura')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Aberto' => 'Aberto',
                        'Em análise' => 'Em análise',
                        'Em execução' => 'Em execução',
                        'Aguardando cliente' => 'Aguardando cliente',
                        'Resolvido' => 'Resolvido',
                        'Cancelado' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options([
                        'Baixa' => 'Baixa',
                        'Média' => 'Média',
                        'Alta' => 'Alta',
                        'Urgente' => 'Urgente',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
