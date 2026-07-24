<?php

namespace App\Services;

use App\Models\User;
use App\Models\ViaConversation;
use App\Models\ViaMessage;
use Illuminate\Support\Facades\DB;

class ViaChatService
{
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
    public function sendHomologationMessage(User $user, string $content): array
    {
        return DB::transaction(function () use ($user, $content): array {
            $conversation = $this->openOrCreateConversation($user);

            $userMessage = $conversation->messages()->create([
                'role' => 'user',
                'content' => $content,
                'status' => 'completed',
            ]);

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => 'Mensagem recebida em homologação. O histórico já está sendo salvo. A integração com o roteador de IA será conectada na próxima etapa.',
                'status' => 'completed',
                'provider' => 'homologation',
                'model' => 'fixed-response',
            ]);

            if ($conversation->title === 'Nova conversa com a VIA') {
                $conversation->title = mb_strimwidth($content, 0, 80, '…');
            }

            $conversation->last_message_at = now();
            $conversation->save();

            return compact('conversation', 'userMessage', 'assistantMessage');
        });
    }
}
