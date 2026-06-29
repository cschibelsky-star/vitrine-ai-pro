<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProntuarioResource\Pages;
use App\Models\Prontuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProntuarioResource extends Resource
{
    protected static ?string $model = Prontuario::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Factory';

    protected static ?string $navigationLabel = 'Prontuários';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('animal_id')->relationship('animal', 'nome')->searchable()->preload()->required(),
                    Forms\Components\Textarea::make('descricao')->label('Descricao'),
                    Forms\Components\Textarea::make('diagnostico')->label('Diagnostico'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('animal.nome')->label('Animal')->searchable(),
                    Tables\Columns\TextColumn::make('descricao')->limit(40)->toggleable(),
                    Tables\Columns\TextColumn::make('diagnostico')->limit(40)->toggleable(),
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
            'index' => Pages\ListProntuarios::route('/'),
            'create' => Pages\CreateProntuario::route('/create'),
            'view' => Pages\ViewProntuario::route('/{record}'),
            'edit' => Pages\EditProntuario::route('/{record}/edit'),
        ];
    }
}
