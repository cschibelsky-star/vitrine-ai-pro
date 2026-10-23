<?php

namespace App\Console\Commands;

use App\Models\AiProvider;
use App\Services\Ai\AiMediaGenerationService;
use Illuminate\Console\Command;

class RoteiaImageSmokeCommand extends Command
{
    protected $signature = 'ai:roteia-image-smoke
        {--model=google/gemini-3.1-flash-image : Exact Roteia model identifier}
        {--max-brl=3.00 : Maximum estimated cost approved in BRL}
        {--test-id=roteia-image-smoke-v1 : Unique idempotency identifier}
        {--execute : Send one potentially billable request instead of dry-run}
        {--confirm-charge= : Must be EXECUTAR for a billable request}';

    protected $description = 'One-shot, explicit-cost-gated Roteia image smoke test using server-side credentials';

    public function handle(): int
    {
        // These are catalog reference costs for one 1K image, not guaranteed charges.
        $estimatedCosts = [
            'google/gemini-3.1-flash-image' => 0.48,
            'recraft/recraft-v4.1-flash' => 0.05,
            'bytedance-seed/seedream-5-0-flash' => 0.13,
            'black-forest-labs/flux.2-pro' => 0.23,
            'openai/gpt-image-2.5-sunburst' => 0.28,
        ];

        $model = trim((string) $this->option('model'));
        $maxRaw = (string) $this->option('max-brl');
        $testId = (string) $this->option('test-id');
        if (! array_key_exists($model, $estimatedCosts)) {
            $this->error('Model is not in the reviewed image-smoke allowlist.');
            return self::FAILURE;
        }
        if (! preg_match('/^\d+(\.\d{1,2})?$/D', $maxRaw)) {
            $this->error('Invalid --max-brl value.');
            return self::FAILURE;
        }
        $maxBrl = (float) $maxRaw;
        if ($maxBrl <= 0 || $maxBrl > 3 || $estimatedCosts[$model] > $maxBrl) {
            $this->error('Cost preflight rejected. Maximum is R$ 3.00; model reference cost exceeds the limit.');
            return self::FAILURE;
        }
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{4,63}$/D', $testId)) {
            $this->error('Invalid test-id (5-64 letters, digits, underscores or hyphens).');
            return self::FAILURE;
        }

        $this->line(json_encode([
            'test_id' => $testId,
            'model' => $model,
            'max_approved_brl' => $maxBrl,
            'estimated_catalog_brl' => $estimatedCosts[$model],
            'mode' => $this->option('execute') ? 'real' : 'dry-run',
            'notice' => 'No provider-side hard cap exists. This command makes at most one request per test-id; actual provider billing must be reconciled.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (! $this->option('execute')) {
            return self::SUCCESS;
        }
        if ($this->option('confirm-charge') !== 'EXECUTAR') {
            $this->error('Real generation requires --execute --confirm-charge=EXECUTAR.');
            return self::FAILURE;
        }
        if (! env('ROTEIA_API_KEY') || ! env('ROTEIA_BASE_URL')) {
            $this->error('Roteia credentials or base URL not configured in Core runtime.');
            return self::FAILURE;
        }

        $dir = storage_path('app/private/roteia-smoke');
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            $this->error('Unable to create local idempotency directory.');
            return self::FAILURE;
        }
        $lockPath = $dir.'/'.$testId.'.json';
        $handle = fopen($lockPath, 'c+');
        if (! is_resource($handle)) {
            $this->error('Unable to establish idempotency lock.');
            return self::FAILURE;
        }
        try {
            if (! flock($handle, LOCK_EX)) {
                $this->error('Unable to lock test-id.');
                return self::FAILURE;
            }
            rewind($handle);
            if (trim((string) stream_get_contents($handle)) !== '') {
                $this->error('test-id already attempted; do not repeat a potentially billable request.');
                return self::FAILURE;
            }
            $record = json_encode([
                'test_id' => $testId,
                'model' => $model,
                'attempted_at' => now()->toIso8601String(),
                'estimated_catalog_brl' => $estimatedCosts[$model],
                'state' => 'attempted',
            ]);
            ftruncate($handle, 0);
            rewind($handle);
            if (fwrite($handle, $record) !== strlen($record) || ! fflush($handle)) {
                $this->error('Unable to persist pre-request attempt marker.');
                return self::FAILURE;
            }

            // Service handles image decoding, safe storage and optional usage telemetry.
            $runner = new class extends AiMediaGenerationService {
                public function once(AiProvider $provider, string $model, string $prompt): array
                {
                    return $this->generateRoteiaMedia($provider, 'image_generation', $prompt, $model);
                }
            };
            $provider = new AiProvider(['slug' => 'roteia', 'config' => []]);
            $result = $runner->once(
                $provider,
                $model,
                'Uma rota luminosa conectando o Brasil aos laboratórios de IA.'
            );
            $report = [
                'test_id' => $testId,
                'status' => $result['status'] ?? 'unknown',
                'provider_request_id' => data_get($result, 'metadata.provider_request_id'),
                'http_status' => data_get($result, 'metadata.http_status'),
                'asset_path' => $result['asset_path'] ?? null,
                'provider_cost_brl' => data_get($result, 'metadata.provider_cost_brl'),
                'telemetry_failed' => data_get($result, 'metadata.telemetry_failed', false),
                'phase' => data_get($result, 'metadata.phase'),
            ];
            $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return ($result['status'] ?? '') === 'Concluído' ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $error) {
            // Never echo credentials, provider response bodies or sensitive exceptions.
            $this->error('Image test failed after attempt marker; verify Roteia billing and Core logs before another test-id.');
            return self::FAILURE;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
