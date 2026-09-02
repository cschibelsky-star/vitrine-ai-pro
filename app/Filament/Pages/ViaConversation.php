<?php

namespace App\Filament\Pages;

use App\Shared\AI\Models\ViaConversation as ViaConversationModel;
use App\Shared\AI\Models\ViaMessage;
use App\Shared\AI\Services\ViaConversationIntentRouter;
use App\Shared\AI\Services\ViaObserverMissionOrchestrator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Throwable;

class ViaConversation extends Page
{
    /**
     * U6.x: a VIA permanece como backend/página de suporte, mas a experiência
     * principal será um widget flutuante sobre o Centro Operacional.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = '07 · IA Center';
    protected static ?string $navigationLabel = 'VIA · Conversa';
    protected static ?string $title = 'VIA · Conversa';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.via-conversation';

    public string $message = '';
    public array $messages = [];
    public array $sessions = [];
    public ?array $lastMission = null;
    public ?int $conversationId = null;
    public string $domain = 'factory';
    public string $targetProjectId = 'factory';
    public bool $sending = false;

    public function mount(): void
    {
        $latest = ViaConversationModel::query()
            ->where('user_id', auth()->id())
            ->latest('last_activity_at')
            ->latest('id')
            ->first();

        if ($latest) {
            $this->loadConversation($latest->id);
        } else {
            $this->newConversation();
        }

        $this->refreshSessions();
    }

    public function newConversation(): void
    {
        if ($this->sending) {
            return;
        }

        $conversation = ViaConversationModel::create([
            'user_id' => auth()->id(),
            'title' => 'Nova conversa VIA',
            'domain' => 'factory',
            'target_project_id' => 'factory',
            'mode' => 'OBSERVER',
            'last_activity_at' => now(),
        ]);

        ViaMessage::create([
            'via_conversation_id' => $conversation->id,
            'role' => 'via',
            'content' => 'VIA operacional em modo OBSERVER. Posso analisar a Factory, identificar riscos, organizar prioridades e recomendar próximos passos. Ações que alterem sistema, arquivos, banco, deploy ou infraestrutura exigem autorização explícita do owner.',
            'metadata' => ['system_intro' => true],
        ]);

        $this->conversationId = $conversation->id;
        $this->domain = 'factory';
        $this->targetProjectId = 'factory';
        $this->message = '';
        $this->lastMission = null;
        $this->loadMessages($conversation);
        $this->refreshSessions();
    }

    public function loadConversation(int $conversationId): void
    {
        if ($this->sending) {
            return;
        }

        $conversation = ViaConversationModel::query()
            ->where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->conversationId = $conversation->id;
        $this->domain = $conversation->domain;
        $this->targetProjectId = $conversation->target_project_id;
        $this->message = '';
        $this->loadMessages($conversation);
        $this->restoreLastMission();
        $this->refreshSessions();
    }

    public function sendMessage(ViaObserverMissionOrchestrator $orchestrator, ViaConversationIntentRouter $intentRouter): void
    {
        if ($this->sending) {
            return;
        }

        $message = trim($this->message);

        if ($message === '') {
            return;
        }

        if (mb_strlen($message) > 4000) {
            Notification::make()->title('Mensagem muito longa')->body('Use no máximo 4.000 caracteres por mensagem.')->warning()->send();
            return;
        }

        $conversation = $this->currentConversation();

        if ($this->domain !== 'factory' || $this->targetProjectId !== 'factory') {
            Notification::make()->title('Escopo ainda não habilitado')->body('Nesta versão, a VIA conversa com evidência real somente sobre a Factory.')->warning()->send();
            return;
        }

        $this->persistMessage($conversation, 'user', $message);
        $this->message = '';
        $this->sending = true;

        $localResponse = $intentRouter->resolve($message, request());
        if ($localResponse !== null) {
            $this->persistMessage(
                $conversation,
                'via',
                (string) $localResponse['content'],
                (array) ($localResponse['metadata'] ?? []) + ['intent' => $localResponse['intent'] ?? 'local_context']
            );
            $this->sending = false;
            $conversation->update(['last_activity_at' => now()]);
            $this->loadMessages($conversation->fresh());
            $this->refreshSessions();
            return;
        }

        if ($conversation->title === 'Nova conversa VIA') {
            $conversation->update(['title' => Str::limit(preg_replace('/\s+/', ' ', $message), 72, '…')]);
        }

        try {
            $result = $orchestrator->execute([
                'domain' => $this->domain,
                'target_project_id' => $this->targetProjectId,
                'profile' => 'balanced',
                'mission' => $message,
            ]);

            $report = trim((string) ($result['report'] ?? ''));
            $missionRecord = (array) ($result['mission_record'] ?? []);
            $evidencePack = (array) ($result['evidence_pack'] ?? []);
            $structuredRuntime = (array) ($result['structured_runtime'] ?? []);
            $chain = (array) ($structuredRuntime['chain'] ?? []);
            $decisions = (array) ($structuredRuntime['decisions'] ?? []);
            $responseMeta = (array) data_get($result, 'result.analysis.response_meta', []);
            $usage = (array) data_get($result, 'result.analysis.usage', []);

            if ($report === '') {
                $report = 'A análise foi concluída, mas não retornou conteúdo textual. Consulte os metadados da missão antes de repetir a chamada.';
            }

            $agentStates = [];
            foreach (['orchestrator', 'project_manager', 'architect', 'qa', 'auditor', 'report'] as $role) {
                $decision = (array) ($decisions[$role] ?? []);
                $validation = (array) data_get($chain, "results.{$role}", []);
                $agentStates[$role] = [
                    'decision_state' => $decision['decision_state'] ?? 'unavailable',
                    'evidence_refs' => array_values((array) ($decision['evidence_refs'] ?? [])),
                    'valid' => (bool) ($validation['valid'] ?? false),
                    'handoff_allowed' => (bool) ($validation['handoff_allowed'] ?? false),
                    'errors' => array_values((array) ($validation['errors'] ?? [])),
                ];
            }

            $meta = [
                'mission_id' => $missionRecord['mission_id'] ?? null,
                'evidence_sha256' => $evidencePack['sha256'] ?? null,
                'grounding' => $missionRecord['grounding_policy'] ?? 'strict_v1',
                'runtime_version' => $missionRecord['runtime_version'] ?? null,
                'structured_runtime_valid' => (bool) ($structuredRuntime['valid'] ?? false),
                'structured_runtime_fail_closed' => (bool) ($structuredRuntime['fail_closed'] ?? true),
                'role_contracts_version' => $missionRecord['role_contracts_version'] ?? null,
                'decision_contracts_version' => $missionRecord['decision_contracts_version'] ?? null,
                'finish_reason' => $responseMeta['finish_reason'] ?? null,
                'content_length' => $responseMeta['content_length'] ?? null,
                'tokens' => $usage['total_tokens'] ?? null,
                'cost_brl' => $usage['provider_cost_brl'] ?? null,
                'pipeline' => ['orchestrator', 'project_manager', 'architect', 'qa', 'auditor', 'report'],
                'agent_states' => $agentStates,
                'chain_errors' => array_values((array) ($chain['errors'] ?? [])),
            ];

            $this->persistMessage($conversation, 'via', $report, $meta);
            $this->lastMission = $meta;
        } catch (Throwable $e) {
            report($e);
            $this->persistMessage($conversation, 'via', 'Não consegui concluir esta análise. O erro técnico foi registrado internamente. Nenhuma ação operacional foi executada.', ['status' => 'error']);
            Notification::make()->title('Falha na análise VIA')->body('Nenhuma alteração operacional foi executada.')->danger()->send();
        } finally {
            $this->sending = false;
            $conversation->update(['last_activity_at' => now()]);
            $this->loadMessages($conversation->fresh());
            $this->refreshSessions();
        }
    }

    public function clearConversation(): void
    {
        $this->newConversation();
    }

    private function currentConversation(): ViaConversationModel
    {
        if (! $this->conversationId) {
            $this->newConversation();
        }

        return ViaConversationModel::query()
            ->where('id', $this->conversationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    private function persistMessage(ViaConversationModel $conversation, string $role, string $content, ?array $metadata = null): void
    {
        ViaMessage::create([
            'via_conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);

        $conversation->update(['last_activity_at' => now()]);
    }

    private function loadMessages(ViaConversationModel $conversation): void
    {
        $messages = $conversation->messages()
            ->reorder()
            ->latest('id')
            ->limit(100)
            ->get()
            ->sortBy('id')
            ->values();

        $this->messages = $messages->map(fn (ViaMessage $message): array => [
            'role' => $message->role,
            'content' => $message->content,
            'meta' => $message->metadata,
            'created_at' => $message->created_at?->format('d/m H:i'),
        ])->all();
    }

    private function restoreLastMission(): void
    {
        $meta = collect($this->messages)->reverse()->first(
            fn (array $item): bool => ($item['role'] ?? null) === 'via' && ! empty($item['meta']['mission_id'])
        );

        $this->lastMission = $meta['meta'] ?? null;
    }

    private function refreshSessions(): void
    {
        $this->sessions = ViaConversationModel::query()
            ->where('user_id', auth()->id())
            ->latest('last_activity_at')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (ViaConversationModel $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'domain' => $conversation->domain,
                'target_project_id' => $conversation->target_project_id,
                'active' => $conversation->id === $this->conversationId,
                'last_activity' => $conversation->last_activity_at?->format('d/m H:i'),
            ])->all();
    }
}
