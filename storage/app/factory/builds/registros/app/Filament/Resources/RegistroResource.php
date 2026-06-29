<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistroResource\Pages\CreateRegistro;
use App\Filament\Resources\RegistroResource\Pages\EditRegistro;
use App\Filament\Resources\RegistroResource\Pages\ListRegistros;
use App\Filament\Resources\RegistroResource\Pages\ViewRegistro;
use App\Models\Registro;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistroResource extends Resource
{
    protected static ?string $model = Registro::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Registros';
    protected static ?string $navigationLabel = 'Registros';
    protected static ?string $slug = 'registros';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    TextInput::make('nome')->label('Nome')->maxLength(255),
                    Textarea::make('descricao')->label('Descricao')->columnSpanFull(),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('descricao')->label('Descricao')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistros::route('/'),
            'create' => CreateRegistro::route('/create'),
            'view' => ViewRegistro::route('/{record}'),
            'edit' => EditRegistro::route('/{record}/edit'),
        ];
    }
}
