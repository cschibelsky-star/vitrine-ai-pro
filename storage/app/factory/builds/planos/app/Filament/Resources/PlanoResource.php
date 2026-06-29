<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanoResource\Pages\CreatePlano;
use App\Filament\Resources\PlanoResource\Pages\EditPlano;
use App\Filament\Resources\PlanoResource\Pages\ListPlanos;
use App\Filament\Resources\PlanoResource\Pages\ViewPlano;
use App\Models\Plano;
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

class PlanoResource extends Resource
{
    protected static ?string $model = Plano::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Planos';
    protected static ?string $navigationLabel = 'Planos';
    protected static ?string $slug = 'planos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('cliente_id')->label('Cliente')->relationship('cliente', 'nome')->searchable()->preload(),
                    TextInput::make('titulo')->label('Titulo')->maxLength(255),
                    Textarea::make('descricao')->label('Descricao')->columnSpanFull(),
                    DatePicker::make('prazo')->label('Prazo'),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nome')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('titulo')->label('Titulo')->searchable()->sortable(),
                TextColumn::make('descricao')->label('Descricao')->searchable()->sortable(),
                TextColumn::make('prazo')->label('Prazo')->searchable()->sortable(),
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
            'index' => ListPlanos::route('/'),
            'create' => CreatePlano::route('/create'),
            'view' => ViewPlano::route('/{record}'),
            'edit' => EditPlano::route('/{record}/edit'),
        ];
    }
}
