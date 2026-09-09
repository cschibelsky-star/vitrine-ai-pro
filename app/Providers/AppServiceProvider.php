<?php

namespace App\Providers;

use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use App\Marketing\Infrastructure\Video\HeygenIncrementalSceneRenderer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VideoSceneRenderer::class, function ($app) {
            return match ((string) config('marketing_video.provider', 'gemini_veo')) {
                'gemini_veo' => $app->make(GeminiVeoSceneRenderer::class),
                'heygen' => $app->make(HeygenIncrementalSceneRenderer::class),
                default => throw new \RuntimeException('marketing_video_provider_unsupported'),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
