<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Planos';
    protected static ?string $navigationGroup = 'Produtos e Licenças';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Plano';
    protected static ?string $pluralModelLabel = 'Planos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identificação')->columns(2)->schema([
                Forms\Components\Select::make('product_id')->label('Produto')->relationship('product', 'nome')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('nome')->label('Nome do Plano')->required()->maxLength(255),
            ]),
            Section::make('Valores e Cobrança')->columns(3)->schema([
                Forms\Components\TextInput::make('valor_mensal')->label('Valor Mensal (R$)')->numeric()->prefix('R$')->default(0.00)->required(),
                Forms\Components\TextInput::make('valor_implantacao')->label('Valor Implantação (R$)')->numeric()->prefix('R$')->default(0.00),
                Forms\Components\Select::make('ciclo_cobranca')->label('Ciclo de Cobrança')->options(['mensal'=>'Mensal','anual'=>'Anual','implantacao'=>'Implantação','trial'=>'Trial','cortesia'=>'Cortesia'])->default('mensal')->required(),
            ]),
            Section::make('Franquias de IA')->columns(3)->schema([
                Forms\Components\TextInput::make('entitlement_content_credit')
                    ->label('Conteúdos por período')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Quantidade de gerações de conteúdo disponíveis no período.'),
                Forms\Components\TextInput::make('entitlement_video_second')
                    ->label('Vídeo (segundos)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Franquia de geração de vídeo em segundos por período.'),
                Forms\Components\TextInput::make('entitlement_avatar_second')
                    ->label('Avatar (segundos)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Franquia de avatar em segundos por período.'),
            ]),
            Section::make('Detalhes')->columns(1)->schema([
                Forms\Components\Textarea::make('descricao')->label('Descrição')->rows(3),
                Forms\Components\Textarea::make('recursos')
                    ->label('Recursos adicionais / configuração legada')
                    ->rows(5)
                    ->helperText('Conteúdo existente é preservado. As franquias acima são serializadas automaticamente para o Social.'),
                Forms\Components\Select::make('status')->label('Status')->options(['Ativo'=>'Ativo','Inativo'=>'Inativo'])->default('Ativo')->required(),
            ]),
        ]);
    }

    public static function hydrateEntitlements(array $data): array
    {
        $resources = trim((string) ($data['recursos'] ?? ''));
        $decoded = json_decode($resources, true);
        $config = is_array($decoded) ? $decoded : [];

        if (! is_array($decoded) && $resources !== '') {
            $config['legacy_text'] = $resources;
        }

        $data['entitlement_content_credit'] = $config['content_credit'] ?? null;
        $data['entitlement_video_second'] = $config['video_second'] ?? null;
        $data['entitlement_avatar_second'] = $config['avatar_second'] ?? null;

        if (isset($config['legacy_text']) && is_string($config['legacy_text'])) {
            $data['recursos'] = $config['legacy_text'];
        }

        return $data;
    }

    public static function dehydrateEntitlements(array $data): array
    {
        $resources = trim((string) ($data['recursos'] ?? ''));
        $decoded = json_decode($resources, true);
        $config = is_array($decoded) ? $decoded : [];

        if (! is_array($decoded) && $resources !== '') {
            $config['legacy_text'] = $resources;
        }

        foreach ([
            'content_credit' => 'entitlement_content_credit',
            'video_second' => 'entitlement_video_second',
            'avatar_second' => 'entitlement_avatar_second',
        ] as $resourceKey => $formKey) {
            $value = $data[$formKey] ?? null;

            if ($value === null || $value === '') {
                unset($config[$resourceKey]);
            } else {
                $config[$resourceKey] = round(max(0, (float) $value), 2);
            }

            unset($data[$formKey]);
        }

        $data['recursos'] = $config === []
            ? null
            : json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nome')->label('Plano')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('valor_mensal')->label('Valor Mensal')->money('BRL')->sortable(),
            Tables\Columns\TextColumn::make('valor_implantacao')->label('Implantação')->money('BRL')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('ciclo_cobranca')->label('Ciclo')->badge()->color(fn (string $state): string => match ($state) { 'mensal'=>'info', 'anual'=>'success', 'trial'=>'warning', 'cortesia'=>'gray', 'implantacao'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'Ativo'=>'success', 'Inativo'=>'danger', default=>'gray' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('product_id')->label('Produto')->relationship('product', 'nome'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListPlans::route('/'), 'create'=>Pages\CreatePlan::route('/create'), 'edit'=>Pages\EditPlan::route('/{record}/edit')];
    }
}
