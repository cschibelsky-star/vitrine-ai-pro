<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeygenVideoJobResource\Pages;
use App\Models\AiAgent;
use App\Models\AiProvider;
use App\Models\Company;
use App\Models\HeygenAvatar;
use App\Models\HeygenVideoJob;
use App\Services\Heygen\HeygenService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HeygenVideoJobResource extends Resource
{
    protected static ?string $model = HeygenVideoJob::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Inteligência Artificial';
    protected static ?string $navigationLabel = 'Vídeos HeyGen';
    protected static ?string $modelLabel = 'Vídeo HeyGen';
    protected static ?string $pluralModelLabel = 'Vídeos HeyGen';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vídeo IA / HeyGen')->columns(2)->schema([
                Forms\Components\TextInput::make('title')->label('Título')->maxLength(180)->required(),
                Forms\Components\Select::make('company_id')->label('Cliente / Empresa')->options(fn () => self::companyOptions())->searchable()->preload(),
                Forms\Components\Select::make('ai_agent_id')->label('Agente IA')->options(fn () => AiAgent::query()->orderBy('name')->pluck('name', 'id')->toArray())->searchable()->preload(),
                Forms\Components\Select::make('ai_provider_id')->label('Provedor HeyGen')->options(fn () => AiProvider::query()->where('slug', 'heygen')->pluck('name', 'id')->toArray())->searchable()->preload(),
                Forms\Components\Select::make('heygen_avatar_id')->label('Avatar')->options(fn () => HeygenAvatar::query()->orderBy('name')->pluck('name', 'id')->toArray())->searchable()->preload(),
                Forms\Components\Select::make('status')->label('Status')->options(['Pendente'=>'Pendente','Na Fila'=>'Na Fila','Gerando'=>'Gerando','Concluído'=>'Concluído','Erro'=>'Erro'])->default('Pendente')->required(),
                Forms\Components\Textarea::make('script')->label('Roteiro')->rows(8)->columnSpanFull(),
                Forms\Components\TextInput::make('heygen_video_id')->label('ID do Vídeo no HeyGen'),
                Forms\Components\TextInput::make('video_url')->label('URL final do vídeo')->url()->columnSpanFull(),
                Forms\Components\TextInput::make('thumbnail_url')->label('URL da thumbnail')->url()->columnSpanFull(),
                Forms\Components\TextInput::make('duration_seconds')->label('Duração (segundos)')->numeric(),
                Forms\Components\TextInput::make('credits_used')->label('Créditos usados')->numeric()->default(0),
                Forms\Components\Textarea::make('payload')->label('Payload enviado')->rows(3)->columnSpanFull(),
                Forms\Components\Textarea::make('response')->label('Resposta HeyGen')->rows(3)->columnSpanFull(),
                Forms\Components\Textarea::make('error_message')->label('Erro')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('company_label')->label('Cliente')->getStateUsing(fn (HeygenVideoJob $record) => optional($record->company)->nome ?? optional($record->company)->name ?? '-'),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('credits_used')->label('Créditos')->sortable(),
            Tables\Columns\TextColumn::make('heygen_video_id')->label('ID HeyGen')->toggleable(),
            Tables\Columns\TextColumn::make('video_url')->label('Vídeo')->formatStateUsing(fn ($state) => $state ? 'Abrir' : '-')->url(fn (HeygenVideoJob $record) => $record->video_url ?: null, true),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([
            Tables\Actions\Action::make('gerar')->label('Gerar Agora')->icon('heroicon-o-bolt')->requiresConfirmation()->action(function (HeygenVideoJob $record) {
                app(HeygenService::class)->generateVideo($record);
                Notification::make()->title('Job enviado para HeyGen')->success()->send();
            }),
            Tables\Actions\Action::make('atualizar')->label('Atualizar Status')->icon('heroicon-o-arrow-path')->visible(fn (HeygenVideoJob $record) => filled($record->heygen_video_id))->action(function (HeygenVideoJob $record) {
                app(HeygenService::class)->refreshStatus($record);
                Notification::make()->title('Status atualizado')->success()->send();
            }),
            Tables\Actions\EditAction::make()->label('Editar'),
            Tables\Actions\Action::make('assistir')->label('Assistir')->icon('heroicon-o-play')->url(fn (HeygenVideoJob $record) => $record->video_url ?: null, true)->visible(fn (HeygenVideoJob $record) => filled($record->video_url)),
        ]);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->title ?? 'Vídeo HeyGen';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeygenVideoJobs::route('/'),
            'create' => Pages\CreateHeygenVideoJob::route('/create'),
            'edit' => Pages\EditHeygenVideoJob::route('/{record}/edit'),
        ];
    }

    protected static function companyOptions(): array
    {
        return Company::query()->orderBy('id')->get()->mapWithKeys(function ($company) {
            $label = $company->nome ?? $company->name ?? $company->razao_social ?? $company->company_name ?? ('Empresa #' . $company->id);
            return [$company->id => $label];
        })->toArray();
    }
}
