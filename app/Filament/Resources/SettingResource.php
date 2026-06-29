<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Dados da Empresa';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 11;
    protected static ?string $modelLabel = 'Configuração';
    protected static ?string $pluralModelLabel = 'Dados da Empresa';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados da Empresa')->schema([
                Forms\Components\TextInput::make('empresa')->label('Nome da Empresa')->required()->maxLength(255),
                Forms\Components\TextInput::make('telefone')->label('Telefone')->tel()->maxLength(20),
                Forms\Components\TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                Forms\Components\TextInput::make('endereco')->label('Endereço')->maxLength(255),
                Forms\Components\FileUpload::make('logo')->label('Logo')->image()->directory('logos')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('empresa')->label('Empresa'),
            Tables\Columns\TextColumn::make('telefone')->label('Telefone'),
            Tables\Columns\TextColumn::make('email')->label('E-mail'),
            Tables\Columns\TextColumn::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListSettings::route('/'), 'create'=>Pages\CreateSetting::route('/create'), 'edit'=>Pages\EditSetting::route('/{record}/edit')];
    }
}
