<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources;

use App\Factory\Filament\Resources\FactoryBlueprintResource\Pages\CreateFactoryBlueprint;
use App\Factory\Filament\Resources\FactoryBlueprintResource\Pages\EditFactoryBlueprint;
use App\Factory\Filament\Resources\FactoryBlueprintResource\Pages\ListFactoryBlueprints;
use App\Factory\Filament\Resources\FactoryBlueprintResource\Pages\ViewFactoryBlueprint;
use App\Factory\Models\FactoryBlueprint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;

class FactoryBlueprintResource extends Resource
{
    protected static ?string $model = FactoryBlueprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Factory 2.0';

    protected static ?string $navigationLabel = 'Blueprints';

    protected static ?string $modelLabel = 'Blueprint';

    protected static ?string $pluralModelLabel = 'Blueprints';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return auth()->check() && Gate::allows('factory.access');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados principais')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome')
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(self::statusOptions())
                        ->default(self::defaultStatus()),
                    Forms\Components\Textarea::make('description')
                        ->label('Descrição')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Dados técnicos')
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->maxLength(255),
                    Forms\Components\KeyValue::make('metadata')
                        ->label('Metadata')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('project.name')->label('Projeto')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::statusOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['project', 'capability']))
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFactoryBlueprints::route('/'),
            'create' => CreateFactoryBlueprint::route('/create'),
            'view' => ViewFactoryBlueprint::route('/{record}'),
            'edit' => EditFactoryBlueprint::route('/{record}/edit'),
        ];
    }

    protected static function statusOptions(): array
    {
        return config('factory.statuses.blueprints', []);
    }

    protected static function defaultStatus(): ?string
    {
        $options = self::statusOptions();

        return array_key_first($options);
    }
}
