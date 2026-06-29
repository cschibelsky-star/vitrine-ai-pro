<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\VacinaResource\Pages;
use App\Models\Vacina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VacinaResource extends Resource
{
    protected static ?string $model = Vacina::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Módulos Gerados';

    protected static ?string $navigationLabel = 'Vacinas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('animal_id')->relationship('animal', 'nome')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('nome')->label('Nome')->required(),
                    Forms\Components\DatePicker::make('data_aplicacao')->label('Data Aplicacao'),
                    Forms\Components\DatePicker::make('proxima_dose')->label('Proxima Dose'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('animal.nome')->label('Animal')->searchable(),
                    Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('data_aplicacao')->date()->sortable(),
                    Tables\Columns\TextColumn::make('proxima_dose')->date()->sortable(),
                    Tables\Columns\TextColumn::make('status')->searchable()->sortable()
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacinas::route('/'),
            'create' => Pages\CreateVacina::route('/create'),
            'view' => Pages\ViewVacina::route('/{record}'),
            'edit' => Pages\EditVacina::route('/{record}/edit'),
        ];
    }
}
