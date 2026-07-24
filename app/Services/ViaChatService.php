<?php

namespace App\Services;

use App\Models\AiAgent;
use App\Models\User;
use App\Models\ViaConversation;
use App\Models\ViaMessage;
use App\Services\Ai\AiExecutionService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ViaChatService
{
    public function __construct(
        private readonly AiExecutionService $aiExecutionService,
    ) {
    }

    public function openOrCreateConversation(User $user): ViaConversation
    {
        return ViaConversation::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->latest('last_message_at')
            ->latest('id')
            ->first()
            ?? ViaConversation::create([
                'user_id' => $user->getKey(),
                'title' => 'Nova conversa com a VIA',
                'status' => 'active',
            ]);
    }

    /**
     * @return array{conversation: ViaConversation, user_message: ViaMessage, assistant_message: ViaMessage}
     */
    public function sendMessage(User $user, string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('A mensagem da VIA não pode estar vazia.');
        }

        [$conversation, $userMessage] = DB::transaction(function () use ($user, $content): array {
            $conversation = $this->openOrCreateConversation($user);

            $userMessage = $conversation->messages()->create([
                'role' => 'user',
                'content' => $content,
                'status' => 'completed',
            ]);

            if ($conversation->title === 'Nova conversa com a VIA') {
                $conversation->title = mb_strimwidth($content, 0, 80, '…');
            }

            $conversation->last_message_at = now();
            $conversation->save();

            return [$conversation, $userMessage];
        });

        try {
            $agent = $this->resolveViaAgent();
            $prompt = $this->buildPrompt($conversation, $content);
            $execution = $this->aiExecutionService->execute($agent, $prompt);

            $status = mb_strtolower((string) $execution->status);
            $succeeded = in_array($status, ['concluído', 'concluido', 'completed'], true);
            $output = trim((string) $execution->output);

            if (! $succeeded) {
                throw new RuntimeException($output !== '' ? $output : 'A execução da IA não foi concluída.');
            }

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $output !== '' ? $output : 'A VIA não retornou conteúdo nesta execução.',
                'status' => 'completed',
                'provider' => $agent->provider?->provider_type
                    ?? $agent->provider?->type
                    ?? $agent->provider?->slug,
                'model' => $execution->model_name ?? $agent->model_name,
                'metadata' => [
                    'ai_execution_id' => $execution->getKey(),
                    'ai_agent_id' => $agent->getKey(),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => 'Não foi possível concluir a resposta agora. O erro foi registrado para análise técnica.',
                'status' => 'error',
                'provider' => 'internal-router',
                'metadata' => [
                    'error' => $exception->getMessage(),
                ],
            ]);
        }

        $conversation->forceFill(['last_message_at' => now()])->save();

        return compact('conversation', 'userMessage', 'assistantMessage');
    }

    /**
     * Compatibilidade temporária com a primeira versão da página Filament.
     *
     * @return array{conversation: ViaConversation, user_message: ViaMessage, assistant_message: ViaMessage}
     */
    public function sendHomologationMessage(User $user, string $content): array
    {
        return $this->sendMessage($user, $content);
    }

    private function resolveViaAgent(): AiAgent
    {
        $agentId = config('via.agent_id');
        $agentSlug = (string) config('via.agent_slug', 'via-assistente');

        $agent = AiAgent::query()
            ->with('provider')
            ->when($agentId, fn ($query) => $query->whereKey($agentId))
            ->when(! $agentId, function ($query) use ($agentSlug): void {
                $query->where(function ($innerQuery) use ($agentSlug): void {
                    $innerQuery
                        ->where('slug', $agentSlug)
                        ->orWhere('name', 'VIA')
                        ->orWhere('name', 'VIA Assistente');
                });
            })
            ->first();

        if (! $agent) {
            throw new RuntimeException(
                'Agente da VIA não configurado. Cadastre um AiAgent e defina VIA_AGENT_ID ou VIA_AGENT_SLUG.'
            );
        }

        return $agent;
    }

    private function buildPrompt(ViaConversation $conversation, string $currentMessage): string
    {
        $historyLimit = max(2, (int) config('via.history_limit', 20));

        $history = $conversation->messages()
            ->latest('id')
            ->limit($historyLimit)
            ->get(['role', 'content'])
            ->reverse()
            ->map(static function (ViaMessage $message): string {
                $label = $message->role === 'assistant' ? 'VIA' : 'Usuário';

                return $label . ': ' . $message->content;
            })
            ->implode("\n\n");

        return <<<PROMPT
Você é a VIA, assistente interno do ecossistema Vitrine IA Pro.
Responda em português do Brasil, com linguagem clara, objetiva e orientada à execução.
Não invente informações internas. Quando faltar contexto, informe a limitação.

Histórico recente da conversa:
{$history}

Mensagem atual do usuário:
{$currentMessage}
PROMPT;
    }
}
