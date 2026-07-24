<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ViaAssistant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'VIA Assistente';
    protected static ?string $navigationGroup = 'Inteligência Artificial';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'VIA — Assistente da Vitrine IA Pro';
    protected static ?string $slug = 'via-assistente';
    protected static string $view = 'filament.pages.via-assistant';

    public string $message = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public function mount(): void
    {
        abort_unless((bool) config('via.enabled', false), 404);

        $this->messages = [
            [
                'role' => 'assistant',
                'content' => 'Olá. Eu sou a VIA, assistente do ecossistema Vitrine IA Pro. Nesta primeira versão estou operando em homologação.',
            ],
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('via.enabled', false);
    }

    public function sendMessage(): void
    {
        $message = trim($this->message);

        if ($message === '') {
            return;
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Mensagem recebida em homologação. A integração com o roteador de IA será conectada na próxima etapa.',
        ];

        $this->message = '';
    }
}