<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources;

use App\Factory\Filament\Resources\FactoryCapabilityResource\Pages\CreateFactoryCapability;
use App\Factory\Filament\Resources\FactoryCapabilityResource\Pages\EditFactoryCapability;
use App\Factory\Filament\Resources\FactoryCapabilityResource\Pages\ListFactoryCapabilities;
use App\Factory\Filament\Resources\FactoryCapabilityResource\Pages\ViewFactoryCapability;
use App\Factory\Models\FactoryCapability;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;

class FactoryCapabilityResource extends Resource
{
    protected static ?string $model = FactoryCapability::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Factory 2.0';

    protected static ?string $navigationLabel = 'Capabilities';

    protected static ?string $modelLabel = 'Capability';

    protected static ?string $pluralModelLabel = 'Capabilities';

    protected static ?int $navigationSort = 20;

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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('project'))
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFactoryCapabilities::route('/'),
            'create' => CreateFactoryCapability::route('/create'),
            'view' => ViewFactoryCapability::route('/{record}'),
            'edit' => EditFactoryCapability::route('/{record}/edit'),
        ];
    }

    protected static function statusOptions(): array
    {
        return config('factory.statuses.capabilities', []);
    }

    protected static function defaultStatus(): ?string
    {
        $options = self::statusOptions();

        return array_key_first($options);
    }
}
