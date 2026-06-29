<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RegistroResource\Pages;
use App\Models\Registro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegistroResource extends Resource
{
    protected static ?string $model = Registro::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Factory';

    protected static ?string $navigationLabel = 'Registros';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')->label('Nome')->required(),
                    Forms\Components\Textarea::make('descricao')->label('Descricao'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('descricao')->limit(40)->toggleable(),
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
            'index' => Pages\ListRegistros::route('/'),
            'create' => Pages\CreateRegistro::route('/create'),
            'view' => Pages\ViewRegistro::route('/{record}'),
            'edit' => Pages\EditRegistro::route('/{record}/edit'),
        ];
    }
}
