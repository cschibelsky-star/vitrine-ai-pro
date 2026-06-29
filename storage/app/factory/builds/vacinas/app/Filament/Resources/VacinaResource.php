<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacinaResource\Pages\CreateVacina;
use App\Filament\Resources\VacinaResource\Pages\EditVacina;
use App\Filament\Resources\VacinaResource\Pages\ListVacinas;
use App\Filament\Resources\VacinaResource\Pages\ViewVacina;
use App\Models\Vacina;
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

class VacinaResource extends Resource
{
    protected static ?string $model = Vacina::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Vacinas';
    protected static ?string $navigationLabel = 'Vacinas';
    protected static ?string $slug = 'vacinas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('animal_id')->label('Animal')->relationship('animal', 'nome')->searchable()->preload(),
                    TextInput::make('nome')->label('Nome')->maxLength(255),
                    DatePicker::make('data_aplicacao')->label('Data Aplicacao'),
                    DatePicker::make('proxima_dose')->label('Proxima Dose'),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('animal.nome')->label('Animal')->searchable()->sortable(),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('data_aplicacao')->label('Data Aplicacao')->searchable()->sortable(),
                TextColumn::make('proxima_dose')->label('Proxima Dose')->searchable()->sortable(),
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
            'index' => ListVacinas::route('/'),
            'create' => CreateVacina::route('/create'),
            'view' => ViewVacina::route('/{record}'),
            'edit' => EditVacina::route('/{record}/edit'),
        ];
    }
}
