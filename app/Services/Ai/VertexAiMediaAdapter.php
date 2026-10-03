<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VertexAiMediaAdapter
{
    public function __construct(
        private readonly VertexAiCredentials $credentials,
        private readonly VertexAiDailyQuota $quota,
    ) {
    }

    public function generate(string $capability, string $prompt, ?string $model = null): array
    {
        if (! filter_var(env('VERTEX_AI_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Vertex desabilitado.');
        }
        if (! in_array($capability, ['image_generation', 'video_generation'], true) || trim($prompt) === '') {
            throw new RuntimeException('Solicitação Vertex inválida.');
        }

        $video = $capability === 'video_generation';
        $project = trim((string) env('GOOGLE_CLOUD_PROJECT', ''));
        $limit = filter_var(env('VERTEX_AI_DAILY_REQUEST_LIMIT', 0), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1) {
            throw new RuntimeException('Limite diário Vertex não configurado.');
        }
        $model = $model ?: (string) env(
            $video ? 'GOOGLE_VERTEX_VIDEO_MODEL' : 'GOOGLE_VERTEX_IMAGE_MODEL',
            $video ? 'veo-3.0-generate-001' : 'imagen-4.0-generate-001'
        );
        $endpoint = $this->endpoint($project, $model);
        $parameters = ['sampleCount' => 1];
        if ($video) {
            $gcs = trim((string) env('GOOGLE_VERTEX_VIDEO_GCS_URI', ''));
            if (! preg_match('~^gs://[a-z0-9][a-z0-9._-]{1,220}[a-z0-9](?:/.*)?$~D', $gcs)) {
                throw new RuntimeException('Destino GCS Vertex inválido ou ausente.');
            }
            $parameters += ['storageUri' => $gcs, 'durationSeconds' => 8, 'aspectRatio' => '16:9'];
        }

        if ($video && str_starts_with($model, 'veo-3')) {
            $parameters['generateAudio'] = false;
        }

        $token = $this->credentials->accessToken();
        $this->quota->reserve($project, $limit);
        $payload = $this->post($endpoint.($video ? ':predictLongRunning' : ':predict'), $token, [
            'instances' => [['prompt' => $prompt]],
            'parameters' => $parameters,
        ]);

        if ($video) {
            $operation = $payload['name'] ?? null;
            $prefix = 'projects/'.$project.'/locations/'.trim((string) env('GOOGLE_CLOUD_LOCATION', 'us-central1'))
                .'/publishers/google/models/'.$model.'/operations/';
            if (! is_string($operation) || ! str_starts_with($operation, $prefix)) {
                throw new RuntimeException('Vertex não retornou uma operação de vídeo válida.');
            }

            return [
                'status' => 'Processando',
                'operation_id' => $operation,
                'output' => 'Vídeo enviado ao Vertex; aguarda conclusão da operação.',
                'metadata' => ['adapter_ready' => true, 'adapter' => 'vertex_ai', 'model' => $model],
            ];
        }

        $prediction = $payload['predictions'][0] ?? [];
        $encoded = $prediction['bytesBase64Encoded'] ?? null;
        $mime = $prediction['mimeType'] ?? 'image/png';
        $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg'][$mime] ?? null;
        $binary = is_string($encoded) ? base64_decode($encoded, true) : false;
        if (! $extension || $binary === false || $binary === '') {
            throw new RuntimeException('Vertex não retornou imagem utilizável.');
        }

        $disk = config('filesystems.default', 'local');
        $path = 'ai-generated/vertex/'.Str::uuid().'.'.$extension;
        if (! Storage::disk($disk)->put($path, $binary)) {
            throw new RuntimeException('Falha ao armazenar imagem Vertex.');
        }

        return [
            'status' => 'Concluído',
            'output' => 'Imagem gerada pelo Vertex.',
            'asset_path' => $path,
            'metadata' => [
                'adapter_ready' => true, 'adapter' => 'vertex_ai', 'model' => $model,
                'storage_disk' => $disk, 'mime_type' => $mime,
            ],
        ];
    }

    // Polling never submits another generation and does not reserve another daily slot.
    public function poll(string $operation, string $model): array
    {
        if (! filter_var(env('VERTEX_AI_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Vertex desabilitado.');
        }
        $project = trim((string) env('GOOGLE_CLOUD_PROJECT', ''));
        $endpoint = $this->endpoint($project, $model);
        $prefix = 'projects/'.$project.'/locations/'.trim((string) env('GOOGLE_CLOUD_LOCATION', 'us-central1'))
            .'/publishers/google/models/'.$model.'/operations/';
        if (! str_starts_with($operation, $prefix) || strlen($operation) <= strlen($prefix)) {
            throw new RuntimeException('Operação Vertex fora do projeto/modelo configurado.');
        }

        $payload = $this->post($endpoint.':fetchPredictOperation', $this->credentials->accessToken(), [
            'operationName' => $operation,
        ]);
        if (! empty($payload['error'])) {
            return ['status' => 'Erro', 'output' => 'Operação Vertex terminou com erro.'];
        }
        if (empty($payload['done'])) {
            return ['status' => 'Processando', 'output' => 'Vídeo em processamento no Vertex.'];
        }
        $gcs = $payload['response']['videos'][0]['gcsUri'] ?? null;
        if (! is_string($gcs) || ! str_starts_with($gcs, 'gs://')) {
            return ['status' => 'Erro', 'output' => 'Vertex concluiu sem retornar vídeo utilizável.'];
        }

        // GCS is private; never pretend its URI is a public URL.
        return [
            'status' => 'Concluído',
            'output' => 'Vídeo concluído no GCS.',
            'metadata' => ['vertex_gcs_uri' => $gcs, 'adapter' => 'vertex_ai', 'adapter_ready' => true],
        ];
    }

    private function endpoint(string $project, string $model): string
    {
        $location = trim((string) env('GOOGLE_CLOUD_LOCATION', 'us-central1'));
        foreach ([$project, $location] as $segment) {
            if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,127}$/D', $segment)) {
                throw new RuntimeException('Projeto, região ou modelo Vertex inválido.');
            }
        }

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}$/D', $model)) {
            throw new RuntimeException('Modelo Vertex inválido.');
        }

        return 'https://'.$location.'-aiplatform.googleapis.com/v1/projects/'.$project
            .'/locations/'.$location.'/publishers/google/models/'.$model;
    }

    private function post(string $url, string $token, array $body): array
    {
        try {
            // No automatic retries or redirects: avoid duplicate charges and token forwarding.
            $response = Http::withToken($token)->acceptJson()->timeout(120)
                ->withOptions(['allow_redirects' => false])->post($url, $body);
        } catch (Throwable) {
            throw new RuntimeException('Falha de comunicação Vertex; não reenviar automaticamente.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('Vertex retornou HTTP '.$response->status().'.');
        }
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Resposta Vertex inválida.');
        }

        return $payload;
    }
}
