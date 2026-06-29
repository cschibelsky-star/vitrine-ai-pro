<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendamentoResource\Pages\CreateAgendamento;
use App\Filament\Resources\AgendamentoResource\Pages\EditAgendamento;
use App\Filament\Resources\AgendamentoResource\Pages\ListAgendamentos;
use App\Filament\Resources\AgendamentoResource\Pages\ViewAgendamento;
use App\Models\Agendamento;
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

class AgendamentoResource extends Resource
{
    protected static ?string $model = Agendamento::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Agendamentos';
    protected static ?string $navigationLabel = 'Agendamentos';
    protected static ?string $slug = 'agendamentos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('animal_id')->label('Animal')->relationship('animal', 'nome')->searchable()->preload(),
                    DatePicker::make('data_agendamento')->label('Data Agendamento'),
                    TextInput::make('tipo')->label('Tipo')->maxLength(255),
                    Textarea::make('observacoes')->label('Observacoes')->columnSpanFull(),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('animal.nome')->label('Animal')->searchable()->sortable(),
                TextColumn::make('data_agendamento')->label('Data Agendamento')->searchable()->sortable(),
                TextColumn::make('tipo')->label('Tipo')->searchable()->sortable(),
                TextColumn::make('observacoes')->label('Observacoes')->searchable()->sortable(),
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
            'index' => ListAgendamentos::route('/'),
            'create' => CreateAgendamento::route('/create'),
            'view' => ViewAgendamento::route('/{record}'),
            'edit' => EditAgendamento::route('/{record}/edit'),
        ];
    }
}
