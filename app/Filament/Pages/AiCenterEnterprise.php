<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiCenterEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = '06 · IA Center';
    protected static ?string $navigationLabel = 'Agentes';
    protected static ?string $title = 'IA Center';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.ai-center-enterprise';

    public function getViewData(): array
    {
        $catalog = Cache::remember('filament:media-orchestrator:catalog', 60, function (): array {
            try {
                $response = Http::acceptJson()
                    ->timeout(15)
                    ->get('https://api.roteia.ai/catalog/models');

                return $response->successful() ? (array) $response->json() : [];
            } catch (\Throwable) {
                return [];
            }
        });

        $items = (array) ($catalog['data'] ?? $catalog['models'] ?? $catalog);
        $profiles = (array) config('centro_ia.media_orchestrator.profiles', []);
        $capabilities = (array) config('centro_ia.media_orchestrator.capabilities', []);

        return [
            'orchestratorProfiles' => $profiles,
            'orchestratorProviders' => [
                'Roteia' => [
                    'configured' => trim((string) env('ROTEIA_API_KEY', '')) !== '' && trim((string) env('ROTEIA_BASE_URL', '')) !== '',
                    'role' => 'Mídia + texto',
                ],
                'OpenRouter' => [
                    'configured' => trim((string) env('OPENROUTER_API_KEY', '')) !== '',
                    'role' => 'Texto + raciocínio + multimodal',
                ],
                'Gemini direto' => [
                    'configured' => trim((string) env('GEMINI_API_KEY', '')) !== '',
                    'role' => 'Fallback direto',
                ],
                'OpenAI direto' => [
                    'configured' => trim((string) env('OPENAI_API_KEY', '')) !== '',
                    'role' => 'Fallback direto',
                ],
                'HeyGen' => [
                    'configured' => trim((string) env('HEYGEN_API_KEY', '')) !== '',
                    'role' => 'Avatar / apresentador',
                ],
            ],
            'orchestratorModels' => [
                'image' => $this->rankForPanel($items, 'image', $profiles['balanced'] ?? [], $capabilities['image'] ?? []),
                'video' => $this->rankForPanel($items, 'video', $profiles['balanced'] ?? [], $capabilities['video'] ?? []),
            ],
            'orchestratorUpdatedAt' => now()->toIso8601String(),
        ];
    }

    private function rankForPanel(array $items, string $target, array $weights, array $policy): array
    {
        $rows = [];
        $patterns = (array) ($policy['preferred_patterns'] ?? []);

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $model = trim((string) ($item['id'] ?? $item['model'] ?? ''));
            $status = strtolower(trim((string) ($item['status'] ?? 'unknown')));
            $outputs = (array) data_get($item, 'modalities.output', []);
            $modelType = strtolower(trim((string) ($item['modelType'] ?? '')));
            $endpoint = strtolower(trim((string) ($item['endpoint'] ?? '')));

            $matchesTarget = in_array($target, $outputs, true)
                || $modelType === $target
                || ($target === 'image' && $endpoint === 'images')
                || ($target === 'video' && $endpoint === 'videos');

            if ($model === '' || ! $matchesTarget) {
                continue;
            }

            $quality = 50.0;
            $marketRank = (int) ($item['marketRank'] ?? 0);
            if ($marketRank > 0) {
                $quality += max(0, 25 - min(25, $marketRank / 20));
            }

            $suitability = 20.0;
            $speed = 10.0;
            $cost = 10.0;
            $reliability = $status === 'available' ? 20.0 : 0.0;
            $reasons = [];

            foreach ($patterns as $pattern => $rule) {
                if ($pattern !== '' && str_contains(strtolower($model), strtolower((string) $pattern))) {
                    $suitability += (float) ($rule['bonus'] ?? 0);
                    $use = trim((string) ($rule['use'] ?? ''));
                    if ($use !== '') {
                        $reasons[] = $use;
                    }
                }
            }

            if (str_contains(strtolower($model), 'fast') || str_contains(strtolower($model), 'flash')) {
                $speed += 10;
                $reasons[] = 'rápido';
            }

            $price = data_get($item, 'imagePriceEstimate.priceBrl')
                ?? ($item['pricePerUnitBrl'] ?? null);

            if (is_numeric($price)) {
                $price = (float) $price;
                $cost += max(0, 20 - min(20, $price * 5));
            } else {
                $price = null;
            }

            $score = ($quality * (float) ($weights['quality'] ?? 0.40))
                + ($suitability * (float) ($weights['suitability'] ?? 0.25))
                + ($cost * (float) ($weights['cost'] ?? 0.20))
                + ($speed * (float) ($weights['speed'] ?? 0.10))
                + ($reliability * (float) ($weights['reliability'] ?? 0.05));

            if ($status !== 'available') {
                $score = 0;
                $reasons[] = 'indisponível agora';
            } else {
                $reasons[] = 'disponível';
            }

            $rows[] = [
                'model' => $model,
                'status' => $status,
                'score' => round($score, 2),
                'price_brl' => $price,
                'reason' => implode(' · ', array_values(array_unique($reasons))),
                'provider' => str_contains($model, '/') ? explode('/', $model, 2)[0] : $model,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        foreach ($rows as $index => &$row) {
            $row['priority'] = $row['status'] === 'available' ? $index + 1 : null;
        }

        return array_slice($rows, 0, 30);
    }
}
