<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AnimalResource\Pages;
use App\Models\Animal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnimalResource extends Resource
{
    protected static ?string $model = Animal::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Factory';

    protected static ?string $navigationLabel = 'Animais';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')->relationship('cliente', 'nome')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('nome')->label('Nome')->required(),
                    Forms\Components\TextInput::make('especie')->label('Especie'),
                    Forms\Components\TextInput::make('raca')->label('Raca'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nome')->label('Cliente')->searchable(),
                    Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('especie')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('raca')->searchable()->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimais::route('/'),
            'create' => Pages\CreateAnimal::route('/create'),
            'view' => Pages\ViewAnimal::route('/{record}'),
            'edit' => Pages\EditAnimal::route('/{record}/edit'),
        ];
    }
}
