<?php

namespace App\Filament\Pages;

use App\Models\ViaConversation;
use App\Services\ViaChatService;
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

    public ?int $conversationId = null;

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public function mount(ViaChatService $chatService): void
    {
        abort_unless((bool) config('via.enabled', false), 404);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $conversation = $chatService->openOrCreateConversation($user);
        $this->conversationId = $conversation->getKey();
        $this->loadMessages($conversation);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('via.enabled', false);
    }

    public function sendMessage(ViaChatService $chatService): void
    {
        $content = trim($this->message);

        if ($content === '') {
            return;
        }

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $result = $chatService->sendHomologationMessage($user, $content);

        $this->conversationId = $result['conversation']->getKey();
        $this->message = '';
        $this->loadMessages($result['conversation']);

        $this->dispatch('via-message-sent');
    }

    private function loadMessages(ViaConversation $conversation): void
    {
        $this->messages = $conversation->messages()
            ->get(['role', 'content'])
            ->map(static fn ($message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();

        if ($this->messages === []) {
            $this->messages = [[
                'role' => 'assistant',
                'content' => 'Olá. Eu sou a VIA, assistente do ecossistema Vitrine IA Pro. Esta versão está em homologação e já mantém o histórico das conversas.',
            ]];
        }
    }
}
