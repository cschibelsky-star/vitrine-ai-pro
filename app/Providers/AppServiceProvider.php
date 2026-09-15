<?php

namespace App\Providers;

use App\Marketing\Application\VideoFinalizationService;
use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use App\Marketing\Infrastructure\Video\HeygenIncrementalSceneRenderer;
use Illuminate\Support\Facades\URL;
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

        $this->app->singleton(VideoFinalizationService::class, function () {
            $config = (array) config('marketing_video.finalization', []);

            return new VideoFinalizationService(
                ffmpegBinary: (string) ($config['ffmpeg_binary'] ?? 'ffmpeg'),
                ffprobeBinary: (string) ($config['ffprobe_binary'] ?? 'ffprobe'),
                workingDirectory: ($config['working_directory'] ?? null) ?: null,
                allowedHosts: (array) ($config['allowed_hosts'] ?? []),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->environment(['homologation', 'production'])) {
            URL::forceScheme('https');
        }
    }
}
