<?php

use App\Marketing\Application\VideoFinalizationService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('about-master', function () {
    $this->info('Vitrine AI Pro Master Start MVP');
});

Artisan::command('marketing:video-finalize {request_id} {version_id} {source_url} {--logo=}', function (VideoFinalizationService $service) {
    $logo = (string) ($this->option('logo') ?: config('marketing_video.finalization.official_logo_path') ?: base_path('assets/img/logo-vitrine-ai-pro.png'));

    $result = $service->finalizeFromUrl(
        (string) $this->argument('request_id'),
        (string) $this->argument('version_id'),
        (string) $this->argument('source_url'),
        $logo,
    );

    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return 0;
})->purpose('Ingesta um video do provider e gera o master final com identidade oficial.');
