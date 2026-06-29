<?php

namespace App\Services\Heygen;

use App\Models\AiProvider;
use App\Models\HeygenVideoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class HeygenService
{
    protected function provider(): ?AiProvider
    {
        return AiProvider::query()->where('slug', 'heygen')->first();
    }

    protected function apiKey(): string
    {
        $provider = $this->provider();
        return trim((string) ($provider->api_key ?? env('HEYGEN_API_KEY')));
    }

    protected function headers(): array
    {
        return [
            'X-Api-Key' => $this->apiKey(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function testConnection(): array
    {
        if ($this->apiKey() === '') {
            return ['ok' => false, 'status' => 'Sem chave', 'message' => 'API Key HeyGen ausente.'];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get('https://api.heygen.com/v3/avatars');

            if ($response->failed()) {
                return ['ok' => false, 'status' => 'Erro', 'message' => $response->body()];
            }

            return ['ok' => true, 'status' => 'Conectado', 'message' => 'HeyGen respondeu com sucesso.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 'Erro', 'message' => $e->getMessage()];
        }
    }

    public function generateVideo(HeygenVideoJob $job): HeygenVideoJob
    {
        if ($this->apiKey() === '') return $this->fail($job, 'API Key HeyGen ausente.');

        $config = $this->provider()?->config ?? [];
        if (is_string($config)) $config = json_decode($config, true) ?: [];

        $avatarId = $job->avatar?->avatar_id ?: ($config['avatar_id'] ?? null);
        $voiceId = $job->avatar?->voice_id ?: ($config['voice_id'] ?? null);

        if (!$avatarId) return $this->fail($job, 'Avatar ID ausente. Cadastre um avatar ou configure avatar_id no provedor HeyGen.');
        if (!$job->script) return $this->fail($job, 'Roteiro ausente.');

        $payload = [
            'type' => 'avatar',
            'avatar_id' => $avatarId,
            'title' => $job->title ?: 'Vídeo Vitrine AI Pro',
            'aspect_ratio' => '16:9',
            'output_format' => 'mp4',
            'script' => $job->script,
            'callback_url' => URL::to('/api/heygen/callback'),
            'callback_id' => (string) $job->id,
        ];

        if ($voiceId) $payload['voice_id'] = $voiceId;

        $job->update([
            'status' => 'Gerando',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $response = Http::withHeaders($this->headers())
                ->withHeaders(['Idempotency-Key' => 'vip-'.$job->id.'-'.Str::random(12)])
                ->timeout(60)
                ->post('https://api.heygen.com/v3/videos', $payload);

            $body = $response->json();

            if ($response->failed()) return $this->fail($job, $response->body(), $body);

            $videoId = data_get($body, 'data.video_id') ?: data_get($body, 'data.id');

            $job->update([
                'status' => 'Na Fila',
                'heygen_video_id' => $videoId,
                'response' => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);

            return $job->refresh();
        } catch (\Throwable $e) {
            return $this->fail($job, $e->getMessage());
        }
    }

    public function refreshStatus(HeygenVideoJob $job): HeygenVideoJob
    {
        if (!$job->heygen_video_id) return $this->fail($job, 'Job sem heygen_video_id.');

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get('https://api.heygen.com/v3/videos/'.$job->heygen_video_id);

            $body = $response->json();

            if ($response->failed()) return $this->fail($job, $response->body(), $body);

            return $this->applyVideoData($job, data_get($body, 'data', $body), $body);
        } catch (\Throwable $e) {
            return $this->fail($job, $e->getMessage());
        }
    }

    public function handleCallback(array $payload): ?HeygenVideoJob
    {
        $callbackId = data_get($payload, 'callback_id') ?: data_get($payload, 'data.callback_id');
        $videoId = data_get($payload, 'video_id') ?: data_get($payload, 'data.video_id') ?: data_get($payload, 'data.id');

        $job = $callbackId ? HeygenVideoJob::query()->find($callbackId) : null;
        if (!$job && $videoId) $job = HeygenVideoJob::query()->where('heygen_video_id', $videoId)->first();
        if (!$job) return null;

        return $this->applyVideoData($job, data_get($payload, 'data', $payload), $payload);
    }

    protected function applyVideoData(HeygenVideoJob $job, array $data, array $raw): HeygenVideoJob
    {
        $status = strtolower((string) data_get($data, 'status', ''));

        $mapped = match ($status) {
            'completed', 'complete', 'success', 'done' => 'Concluído',
            'failed', 'error' => 'Erro',
            'processing', 'running', 'rendering' => 'Gerando',
            default => $job->status === 'Gerando' ? 'Gerando' : 'Na Fila',
        };

        $job->update([
            'status' => $mapped,
            'video_url' => data_get($data, 'video_url') ?: $job->video_url,
            'thumbnail_url' => data_get($data, 'thumbnail_url') ?: $job->thumbnail_url,
            'duration_seconds' => data_get($data, 'duration') ?: data_get($data, 'duration_seconds') ?: $job->duration_seconds,
            'response' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'error_message' => data_get($data, 'error.message') ?: data_get($data, 'failure_info.message') ?: $job->error_message,
            'finished_at' => in_array($mapped, ['Concluído', 'Erro'], true) ? now() : $job->finished_at,
        ]);

        if ($mapped === 'Concluído' && (float) $job->credits_used <= 0) {
            $credits = max(1, round(($job->duration_seconds ?: 60) / 60, 2));
            $job->update(['credits_used' => $credits]);

            if (class_exists(\App\Models\HeygenCreditLedger::class)) {
                \App\Models\HeygenCreditLedger::create([
                    'company_id' => $job->company_id,
                    'heygen_video_job_id' => $job->id,
                    'type' => 'debit',
                    'amount' => $credits,
                    'description' => 'Consumo automático de créditos HeyGen.',
                ]);
            }
        }

        return $job->refresh();
    }

    protected function fail(HeygenVideoJob $job, string $message, ?array $raw = null): HeygenVideoJob
    {
        $job->update([
            'status' => 'Erro',
            'error_message' => $message,
            'response' => $raw ? json_encode($raw, JSON_UNESCAPED_UNICODE) : $job->response,
            'finished_at' => now(),
        ]);

        return $job->refresh();
    }

    public function markQueued(HeygenVideoJob $job): HeygenVideoJob
    {
        $job->update(['status' => 'Na Fila']);
        return $job->refresh();
    }
}
