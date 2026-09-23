<?php

use App\Marketing\Application\SocialDistributionHandoff;
use App\Marketing\Application\VideoFinalizationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

$pieceVersion = static function (array $job): string {
    return hash('sha256', json_encode([
        $job['id'] ?? '',
        $job['revision_count'] ?? 0,
        $job['asset_url'] ?? '',
        $job['provider_job_ref'] ?? '',
        $job['final_path'] ?? '',
        $job['director_job'] ?? [],
    ], JSON_THROW_ON_ERROR));
};

$resolvePublisherAccount = static function (array $job): array {
    $path = trim((string) ($job['publisher_connection_path'] ?? ''));
    if ($path === '') {
        return [];
    }

    $disk = Storage::disk('local');
    if (! $disk->exists($path)) {
        return [];
    }

    try {
        $decoded = json_decode(
            Crypt::decryptString($disk->get($path)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable) {
        return [];
    }

    if (! is_array($decoded)) {
        return [];
    }

    $pageId = trim((string) ($job['publisher_page_id'] ?? $decoded['selected_page_id'] ?? ''));
    $account = collect((array) ($decoded['accounts'] ?? []))->first(
        static fn (array $item): bool => (string) ($item['page_id'] ?? '') === $pageId
    );

    if (! is_array($account)) {
        return [];
    }

    return [
        ...$account,
        'facebook_page_id' => (string) ($account['page_id'] ?? ''),
        'graph_version' => (string) ($decoded['graph_version'] ?? ''),
        'base_url' => (string) ($decoded['base_url'] ?? 'https://graph.facebook.com'),
    ];
};

$writePiece = static function ($disk, string $path, array $job): void {
    $job['_gallery_etag'] = bin2hex(random_bytes(16));
    $target = $disk->path($path);
    $temporary = tempnam(dirname($target), '.piece-');
    if ($temporary === false) {
        throw new RuntimeException('Unable to allocate gallery temporary file.');
    }

    try {
        $encoded = json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($temporary, $encoded) === false || ! rename($temporary, $target)) {
            throw new RuntimeException('Unable to persist gallery piece.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
};

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

Artisan::command('marketing:publish-due', function (SocialDistributionHandoff $distribution) use ($pieceVersion, $resolvePublisherAccount, $writePiece) {
    $disk = Storage::disk('local');
    $now = CarbonImmutable::now('UTC');
    $summary = ['due' => 0, 'published' => 0, 'processing' => 0, 'skipped' => 0, 'failed' => 0];

    foreach ($disk->allFiles('marketing/gallery') as $path) {
        if (! str_ends_with($path, '.json')) {
            continue;
        }

        Cache::lock('marketing-scheduler:'.hash('sha256', $path), 55)->get(function () use (
            $disk,
            $path,
            $now,
            $distribution,
            $pieceVersion,
            $resolvePublisherAccount,
            $writePiece,
            &$summary
        ): void {
            try {
                $job = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($job)) {
                    $summary['skipped']++;
                    return;
                }

                $publicationStatus = (string) ($job['publication_status'] ?? '');
                if (! in_array($publicationStatus, ['SCHEDULED_PENDING_EXECUTOR', 'PUBLISHING'], true)) {
                    return;
                }

                $scheduledAt = trim((string) ($job['scheduled_at'] ?? ''));
                if ($scheduledAt === '') {
                    $summary['skipped']++;
                    return;
                }

                $dueAt = CarbonImmutable::parse($scheduledAt, 'UTC');
                if ($dueAt->isAfter($now)) {
                    return;
                }

                $summary['due']++;

                if (! hash_equals((string) ($job['approved_version'] ?? ''), $pieceVersion($job))) {
                    $job['publication_status'] = 'REAPPROVAL_REQUIRED';
                    $job['publication_error'] = 'approved_version_changed';
                    $job['updated_at'] = now()->toISOString();
                    $writePiece($disk, $path, $job);
                    $summary['skipped']++;
                    return;
                }

                $account = $resolvePublisherAccount($job);
                if ($account === []) {
                    $job['publication_status'] = 'PUBLISHER_NOT_CONNECTED';
                    $job['publication_error'] = 'publisher_context_unavailable';
                    $job['updated_at'] = now()->toISOString();
                    $writePiece($disk, $path, $job);
                    $summary['skipped']++;
                    return;
                }

                $result = $distribution->publishMetaNow($job, $account);
                $status = (string) ($result['status'] ?? 'FAILED');

                $job['publication_status'] = $status;
                $job['publication_channel'] = $result['channel'] ?? ($job['publication_channel'] ?? 'meta');
                $job['publication_container_id'] = $result['container_id'] ?? ($job['publication_container_id'] ?? null);
                $job['publication_external_id'] = $result['external_id'] ?? ($job['publication_external_id'] ?? null);
                $job['publication_error'] = $result['error'] ?? null;
                $job['publication_requested_at'] = $job['publication_requested_at'] ?? now()->toISOString();
                $job['updated_at'] = now()->toISOString();

                if ($status === 'PUBLISHED') {
                    $job['status'] = 'PUBLICADO';
                    $job['published_at'] = now()->toISOString();
                    $job['scheduled_at'] = null;
                    $job['metrics_status'] = 'PENDING';
                    $job['metrics_next_sync_at'] = now()->addMinutes(10)->toISOString();
                    $summary['published']++;
                } elseif ($status === 'PUBLISHING') {
                    $summary['processing']++;
                } else {
                    $job['publication_retry_count'] = ((int) ($job['publication_retry_count'] ?? 0)) + 1;
                    $summary['failed']++;
                }

                $writePiece($disk, $path, $job);
            } catch (Throwable $exception) {
                report($exception);
                $summary['failed']++;
            }
        });
    }

    $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $summary['failed'] > 0 ? 1 : 0;
})->purpose('Publica automaticamente pecas aprovadas quando o horario editorial chega.');

Artisan::command('marketing:sync-metrics', function (SocialDistributionHandoff $distribution) use ($resolvePublisherAccount, $writePiece) {
    $disk = Storage::disk('local');
    $now = CarbonImmutable::now('UTC');
    $summary = ['due' => 0, 'synced' => 0, 'finalized' => 0, 'skipped' => 0, 'failed' => 0];

    foreach ($disk->allFiles('marketing/gallery') as $path) {
        if (! str_ends_with($path, '.json')) {
            continue;
        }

        Cache::lock('marketing-metrics:'.hash('sha256', $path), 55)->get(function () use (
            $disk,
            $path,
            $now,
            $distribution,
            $resolvePublisherAccount,
            $writePiece,
            &$summary
        ): void {
            try {
                $job = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($job) || (string) ($job['status'] ?? '') !== 'PUBLICADO') {
                    return;
                }

                $nextSync = trim((string) ($job['metrics_next_sync_at'] ?? ''));
                if ($nextSync !== '' && CarbonImmutable::parse($nextSync, 'UTC')->isAfter($now)) {
                    return;
                }

                $publishedAt = trim((string) ($job['published_at'] ?? ''));
                if ($publishedAt !== '' && CarbonImmutable::parse($publishedAt, 'UTC')->lt($now->subDays(30))) {
                    $job['metrics_status'] = 'FINAL';
                    $job['metrics_next_sync_at'] = null;
                    $job['updated_at'] = now()->toISOString();
                    $writePiece($disk, $path, $job);
                    $summary['finalized']++;
                    return;
                }

                $summary['due']++;
                $account = $resolvePublisherAccount($job);
                if ($account === []) {
                    $job['metrics_status'] = 'PUBLISHER_NOT_CONNECTED';
                    $job['metrics_next_sync_at'] = now()->addHour()->toISOString();
                    $job['updated_at'] = now()->toISOString();
                    $writePiece($disk, $path, $job);
                    $summary['skipped']++;
                    return;
                }

                $result = $distribution->fetchMetaPublicationMetrics($job, $account);
                if (($result['ok'] ?? false) === true) {
                    $job['metrics_status'] = 'SYNCED';
                    $job['metrics'] = $result;
                    $job['metrics_last_synced_at'] = now()->toISOString();
                    $job['metrics_next_sync_at'] = now()->addMinutes(30)->toISOString();
                    $summary['synced']++;
                } else {
                    $job['metrics_status'] = (string) ($result['status'] ?? 'METRICS_RETRY');
                    $job['metrics_error'] = (string) ($result['error'] ?? 'metrics_sync_failed');
                    $job['metrics_next_sync_at'] = now()->addHour()->toISOString();
                    $summary['failed']++;
                }

                $job['updated_at'] = now()->toISOString();
                $writePiece($disk, $path, $job);
            } catch (Throwable $exception) {
                report($exception);
                $summary['failed']++;
            }
        });
    }

    $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $summary['failed'] > 0 ? 1 : 0;
})->purpose('Sincroniza metadados e engajamento basico das publicacoes Meta confirmadas.');

Schedule::command('marketing:publish-due')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('marketing:sync-metrics')
    ->everyTenMinutes()
    ->withoutOverlapping();
