<?php

namespace App\Services\Ai;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Models\AiMediaGeneration;
use App\Models\AiProvider;
use Illuminate\Support\Str;

class AiRoutingService
{
    public function __construct(
        private readonly AiExecutionService $executor,
        private readonly AiMediaGenerationService $mediaGenerator,
    ) {
    }

    public function execute(AiAgent $agent, string $prompt, ?string $capability = null): AiExecution|AiMediaGeneration
    {
        $capability = $capability ?: $this->classifyCapability($prompt);
        $route = $this->resolveRoute($capability);
        $provider = $this->resolveProvider($route['providers'], $capability);

        if (! $provider) {
            return $this->executor->execute($agent, $prompt);
        }

        $model = data_get($provider->config, 'models.'.$capability)
            ?: data_get($provider->config, 'model_default');

        if ($this->isMediaCapability($capability)) {
            return $this->mediaGenerator->generate($agent, $provider, $capability, $prompt, $model);
        }

        return $this->executor->execute($agent, $prompt, $provider, $model);
    }

    public function classifyCapability(string $prompt): string
    {
        $text = Str::lower($prompt);

        if ($this->containsAny($text, ['avatar', 'apresentador virtual', 'porta-voz virtual'])) {
            return 'avatar_video';
        }

        if ($this->containsAny($text, ['vídeo', 'video', 'reel', 'animação', 'animacao', 'clipe'])) {
            return 'video_generation';
        }

        if ($this->containsAny($text, ['imagem', 'arte', 'criativo', 'banner', 'foto', 'carrossel visual'])) {
            return 'image_generation';
        }

        if ($this->containsAny($text, ['revisar', 'auditar', 'criticar', 'validar campanha'])) {
            return 'critical_review';
        }

        if ($this->containsAny($text, ['estratégia', 'estrategia', 'campanha', 'planejamento', 'posicionamento', 'calendário', 'calendario'])) {
            return 'marketing_strategy';
        }

        return 'copy';
    }

    public function resolveRoute(string $capability): array
    {
        return match ($capability) {
            'image_generation' => ['providers' => ['google', 'gemini'], 'capability' => $capability],
            'video_generation' => ['providers' => ['google', 'gemini'], 'capability' => $capability],
            'avatar_video' => ['providers' => ['heygen'], 'capability' => $capability],
            'critical_review' => ['providers' => ['openai', 'gemini'], 'capability' => $capability],
            'marketing_strategy', 'copy' => ['providers' => ['gemini', 'openai'], 'capability' => $capability],
            default => ['providers' => ['gemini', 'openai'], 'capability' => $capability],
        };
    }

    protected function resolveProvider(array $slugs, string $capability): ?AiProvider
    {
        foreach ($slugs as $slug) {
            $provider = AiProvider::query()
                ->where('slug', $slug)
                ->where('status', 'ativo')
                ->first();

            if (! $provider) {
                continue;
            }

            $capabilities = (array) data_get($provider->config, 'capabilities', []);

            if ($capabilities === [] || in_array('*', $capabilities, true) || in_array($capability, $capabilities, true)) {
                return $provider;
            }
        }

        return null;
    }

    protected function isMediaCapability(string $capability): bool
    {
        return in_array($capability, ['image_generation', 'video_generation', 'avatar_video'], true);
    }

    protected function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
