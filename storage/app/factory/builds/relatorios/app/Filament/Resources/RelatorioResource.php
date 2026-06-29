<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RelatorioResource\Pages\CreateRelatorio;
use App\Filament\Resources\RelatorioResource\Pages\EditRelatorio;
use App\Filament\Resources\RelatorioResource\Pages\ListRelatorios;
use App\Filament\Resources\RelatorioResource\Pages\ViewRelatorio;
use App\Models\Relatorio;
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

class RelatorioResource extends Resource
{
    protected static ?string $model = Relatorio::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Relatórios';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $slug = 'relatorios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('cliente_id')->label('Cliente')->relationship('cliente', 'nome')->searchable()->preload(),
                    TextInput::make('titulo')->label('Titulo')->maxLength(255),
                    Textarea::make('conteudo')->label('Conteudo')->columnSpanFull(),
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
                TextColumn::make('conteudo')->label('Conteudo')->searchable()->sortable(),
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
            'index' => ListRelatorios::route('/'),
            'create' => CreateRelatorio::route('/create'),
            'view' => ViewRelatorio::route('/{record}'),
            'edit' => EditRelatorio::route('/{record}/edit'),
        ];
    }
}
