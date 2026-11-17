<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about-master', function () {
    $this->info('Vitrine AI Pro Master Start MVP');
});

Artisan::command('vertex:poll-media', function () {
    if (! filter_var(env('VERTEX_AI_ENABLED', false), FILTER_VALIDATE_BOOL)) {
        $this->info('Vertex desabilitado.');
        return 0;
    }

    $failures = 0;
    \App\Models\AiMediaGeneration::query()
        ->where('status', 'Processando')
        ->where('capability', 'video_generation')
        ->where('metadata->provider_slug', 'vertex-ai')
        ->whereNotNull('operation_id')
        ->chunkById(50, function ($generations) use (&$failures) {
            foreach ($generations as $generation) {
                try {
                    app(\App\Services\Ai\AiMediaGenerationService::class)->refreshVertexVideo($generation);
                } catch (\Throwable) {
                    // Keep pending on transient polling failures; never resubmit a generation.
                    $failures++;
                }
            }
        });

    $this->info('Consulta Vertex concluída. Falhas de consulta: '.$failures);
    return $failures > 0 ? 1 : 0;
})->purpose('Consulta operações Vertex existentes sem gerar novamente');

\Illuminate\Support\Facades\Schedule::command('vertex:poll-media')
    ->everyMinute()->withoutOverlapping();
