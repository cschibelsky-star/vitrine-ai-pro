<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicitacoResource\Pages\CreateLicitaco;
use App\Filament\Resources\LicitacoResource\Pages\EditLicitaco;
use App\Filament\Resources\LicitacoResource\Pages\ListLicitacoes;
use App\Filament\Resources\LicitacoResource\Pages\ViewLicitaco;
use App\Models\Licitaco;
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

class LicitacoResource extends Resource
{
    protected static ?string $model = Licitaco::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Licitações';
    protected static ?string $navigationLabel = 'Licitações';
    protected static ?string $slug = 'licitacoes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    TextInput::make('numero')->label('Numero')->maxLength(255),
                    Textarea::make('objeto')->label('Objeto')->columnSpanFull(),
                    TextInput::make('modalidade')->label('Modalidade')->maxLength(255),
                    DatePicker::make('data_abertura')->label('Data Abertura'),
                    TextInput::make('valor_estimado')->label('Valor Estimado')->numeric(),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')->label('Numero')->searchable()->sortable(),
                TextColumn::make('objeto')->label('Objeto')->searchable()->sortable(),
                TextColumn::make('modalidade')->label('Modalidade')->searchable()->sortable(),
                TextColumn::make('data_abertura')->label('Data Abertura')->searchable()->sortable(),
                TextColumn::make('valor_estimado')->label('Valor Estimado')->searchable()->sortable(),
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
            'index' => ListLicitacoes::route('/'),
            'create' => CreateLicitaco::route('/create'),
            'view' => ViewLicitaco::route('/{record}'),
            'edit' => EditLicitaco::route('/{record}/edit'),
        ];
    }
}
