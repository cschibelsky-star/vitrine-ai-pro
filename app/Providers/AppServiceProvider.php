<?php

namespace App\Providers;

use App\Marketing\Application\VideoFinalizationService;
use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Marketing video policy: Veo is the default production renderer.
        // HeyGen is reserved for explicit presenter jobs using the approved avatar + cloned voice flow.
        $this->app->bind(VideoSceneRenderer::class, fn ($app) => $app->make(GeminiVeoSceneRenderer::class));

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
