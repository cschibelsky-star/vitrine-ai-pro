<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $navigationLabel = 'Usuários';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $pluralModelLabel = 'Usuários';

    protected static function companyOptions(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        return Company::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function ($company) {
                $label =
                    ($company->name ?? null)
                    ?: ($company->nome ?? null)
                    ?: ($company->company_name ?? null)
                    ?: ($company->razao_social ?? null)
                    ?: ($company->fantasy_name ?? null)
                    ?: ($company->fantasia ?? null)
                    ?: ('Cliente #' . $company->id);

                return [$company->id => (string) $label];
            })
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Usuário')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Perfil')
                            ->options([
                                'admin' => 'Administrador interno',
                                'client' => 'Cliente',
                            ])
                            ->default('admin')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('company_id')
                            ->label('Cliente / Empresa vinculada')
                            ->options(fn () => static::companyOptions())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('role') === 'client')
                            ->required(fn (Forms\Get $get) => $get('role') === 'client'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Usuário ativo')
                            ->default(true),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'client' ? 'Cliente' : 'Administrador')
                    ->color(fn ($state) => $state === 'client' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Cliente vinculado')
                    ->formatStateUsing(fn ($state, $record) =>
                        $state
                        ?: ($record->company->nome ?? null)
                        ?: ($record->company->company_name ?? null)
                        ?: ($record->company->razao_social ?? null)
                        ?: ($record->company_id ? 'Cliente #' . $record->company_id : 'Interno')
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        'admin' => 'Administrador interno',
                        'client' => 'Cliente',
                    ]),
            ])
            ->actions([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
