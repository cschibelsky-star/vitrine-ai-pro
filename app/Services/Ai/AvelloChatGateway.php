<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Opt-in Avello DEV chat adapter. No production routing is changed.
 * Never persist the API key in provider records or logs.
 */
final class AvelloChatGateway
{
    public function complete(string $model, string $prompt, string $system = ''): array
    {
        if (! (bool) config('avello.enabled', false)) {
            throw new RuntimeException('avello_disabled');
        }

        $key = trim((string) config('avello.key', ''));
        if ($key === '') {
            throw new RuntimeException('avello_not_configured');
        }

        $allowlist = (array) config('avello.models', ['gemini-3.1-flash-lite']);
        if (! in_array($model, $allowlist, true)) {
            throw new RuntimeException('avello_model_not_allowed');
        }

        $base = rtrim((string) config('avello.base_url', 'https://avello.online/v1'), '/');
        if ($base !== 'https://avello.online/v1') {
            throw new RuntimeException('avello_base_url_invalid');
        }

        $messages = [];
        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout(30)
            ->post($base.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
            ]);

        if (! $response->successful()) {
            // Never include upstream body: it may contain prompts or secrets.
            throw new RuntimeException('avello_http_'.$response->status());
        }

        $output = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($output) || trim($output) === '') {
            throw new RuntimeException('avello_empty_response');
        }

        return [
            'model' => $model,
            'text' => $output,
            'usage' => (array) data_get($response->json(), 'usage', []),
        ];
    }
}
