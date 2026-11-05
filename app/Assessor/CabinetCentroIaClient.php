<?php
declare(strict_types=1);

namespace App\Assessor;

use Illuminate\Support\Facades\Http;
use Throwable;

final class CabinetCentroIaClient
{
    public function recommend(array $metrics): array
    {
        $fallback = static fn (string $reason): array => [
            'source' => 'local_rules', 'fallback_reason' => $reason,
        ];
        if (!config('assessor_ai.enabled', false)) {
            return $fallback('disabled');
        }
        $url = (string) config('assessor_ai.broker_url', '');
        $token = (string) config('assessor_ai.token', '');
        if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_QUERY) !== null
            || $token === '') {
            return $fallback('not_configured');
        }
        // Explicit allowlist: no names, citizen data, free text, tenant or user identifiers.
        $safe = [];
        foreach (['open_demands', 'in_progress_demands', 'resolved_demands', 'pending_tasks', 'overdue_tasks'] as $key) {
            $safe[$key] = max(0, (int) ($metrics[$key] ?? 0));
        }
        try {
            $response = Http::withToken($token)->acceptJson()
                ->withHeaders(['X-Vitrine-Project' => 'assessor-online'])
                ->connectTimeout(3)->timeout(20)->withoutRedirecting()
                ->post($url, [
                    'project_id' => 'assessor-online',
                    'capability' => 'assistant_conversation',
                    'input' => [
                        'system' => 'Você auxilia a gestão de um gabinete. Use exclusivamente os indicadores agregados recebidos. Não invente fatos ou pessoas. Não execute ações. Responda apenas JSON: {"recommendations":[{"priority":"high|medium|low","title":"ação sugerida","reason":"justificativa baseada nos indicadores"}]}. Máximo 5 sugestões, título até 160 caracteres e motivo até 500.',
                        'user' => json_encode($safe, JSON_THROW_ON_ERROR),
                    ],
                ]);
            if (!$response->successful()) {
                return $fallback('broker_rejected');
            }
            $body = $response->json();
            if (!is_array($body) || ($body['ok'] ?? null) !== true
                || ($body['project_id'] ?? null) !== 'assessor-online'
                || ($body['capability'] ?? null) !== 'assistant_conversation'
                || !is_scalar($body['execution_id'] ?? null)
                || !is_string($body['output_text'] ?? null)
                || strlen($body['output_text']) > 12000) {
                return $fallback('invalid_response');
            }
            $decoded = json_decode($body['output_text'], true, 16, JSON_THROW_ON_ERROR);
            $items = $decoded['recommendations'] ?? null;
            if (!is_array($items) || !array_is_list($items) || count($items) > 5) {
                return $fallback('invalid_response');
            }
            $clean = [];
            foreach ($items as $item) {
                if (!is_array($item) || !in_array($item['priority'] ?? null, ['high', 'medium', 'low'], true)) {
                    return $fallback('invalid_response');
                }
                foreach (['title' => 160, 'reason' => 500] as $field => $max) {
                    if (!is_string($item[$field] ?? null) || trim($item[$field]) === ''
                        || mb_strlen($item[$field]) > $max || strip_tags($item[$field]) !== $item[$field]) {
                        return $fallback('invalid_response');
                    }
                }
                $clean[] = array_intersect_key($item, array_flip(['priority', 'title', 'reason']));
            }
            return ['source' => 'centro_ia', 'ai_connected' => true,
                'execution_id' => $body['execution_id'], 'recommendations' => $clean];
        } catch (Throwable) {
            // Never log request/response bodies, credentials or provider exceptions.
            return $fallback('broker_unavailable');
        }
    }
}
