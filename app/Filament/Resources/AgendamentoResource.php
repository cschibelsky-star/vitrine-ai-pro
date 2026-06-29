<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AgendamentoResource\Pages;
use App\Models\Agendamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgendamentoResource extends Resource
{
    protected static ?string $model = Agendamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Módulos Gerados';

    protected static ?string $navigationLabel = 'Agendamentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('animal_id')->relationship('animal', 'nome')->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('data_agendamento')->label('Data Agendamento'),
                    Forms\Components\TextInput::make('tipo')->label('Tipo'),
                    Forms\Components\Textarea::make('observacoes')->label('Observacoes'),
                    Forms\Components\TextInput::make('status')->label('Status')->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('animal.nome')->label('Animal')->searchable(),
                    Tables\Columns\TextColumn::make('data_agendamento')->date()->sortable(),
                    Tables\Columns\TextColumn::make('tipo')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('observacoes')->limit(40)->toggleable(),
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
            'index' => Pages\ListAgendamentos::route('/'),
            'create' => Pages\CreateAgendamento::route('/create'),
            'view' => Pages\ViewAgendamento::route('/{record}'),
            'edit' => Pages\EditAgendamento::route('/{record}/edit'),
        ];
    }
}
