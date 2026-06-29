<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProntuarioResource\Pages\CreateProntuario;
use App\Filament\Resources\ProntuarioResource\Pages\EditProntuario;
use App\Filament\Resources\ProntuarioResource\Pages\ListProntuarios;
use App\Filament\Resources\ProntuarioResource\Pages\ViewProntuario;
use App\Models\Prontuario;
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

class ProntuarioResource extends Resource
{
    protected static ?string $model = Prontuario::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Prontuários';
    protected static ?string $navigationLabel = 'Prontuários';
    protected static ?string $slug = 'prontuarios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('animal_id')->label('Animal')->relationship('animal', 'nome')->searchable()->preload(),
                    Textarea::make('descricao')->label('Descricao')->columnSpanFull(),
                    Textarea::make('diagnostico')->label('Diagnostico')->columnSpanFull(),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('animal.nome')->label('Animal')->searchable()->sortable(),
                TextColumn::make('descricao')->label('Descricao')->searchable()->sortable(),
                TextColumn::make('diagnostico')->label('Diagnostico')->searchable()->sortable(),
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
            'index' => ListProntuarios::route('/'),
            'create' => CreateProntuario::route('/create'),
            'view' => ViewProntuario::route('/{record}'),
            'edit' => EditProntuario::route('/{record}/edit'),
        ];
    }
}
