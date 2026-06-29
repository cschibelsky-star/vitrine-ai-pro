<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentoResource\Pages\CreateDocumento;
use App\Filament\Resources\DocumentoResource\Pages\EditDocumento;
use App\Filament\Resources\DocumentoResource\Pages\ListDocumentos;
use App\Filament\Resources\DocumentoResource\Pages\ViewDocumento;
use App\Models\Documento;
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

class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Blueprint: Documentos';
    protected static ?string $navigationLabel = 'Documentos';
    protected static ?string $slug = 'documentos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados principais')->schema([
                    Select::make('registro_id')->label('Registro')->relationship('registro', 'nome')->searchable()->preload(),
                    TextInput::make('nome')->label('Nome')->maxLength(255),
                    TextInput::make('arquivo')->label('Arquivo')->maxLength(255),
                    TextInput::make('status')->label('Status')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registro.nome')->label('Registro')->searchable()->sortable(),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('arquivo')->label('Arquivo')->searchable()->sortable(),
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
            'index' => ListDocumentos::route('/'),
            'create' => CreateDocumento::route('/create'),
            'view' => ViewDocumento::route('/{record}'),
            'edit' => EditDocumento::route('/{record}/edit'),
        ];
    }
}
