<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class VideoFinalizationService
{
    /** @param list<string> $allowedHosts */
    public function __construct(
        private readonly string $ffmpegBinary = 'ffmpeg',
        private readonly string $ffprobeBinary = 'ffprobe',
        private readonly ?string $workingDirectory = null,
        private readonly array $allowedHosts = ['files2.heygen.ai', 'resource2.heygen.ai', 'resource.heygen.ai', 'video.heygen.com'],
    ) {}

    /**
     * Ingests a provider MP4 into Vitrine-controlled storage and renders the final branded master.
     *
     * @return array{request_id:string,version_id:string,status:string,base_path:string,final_path:string,manifest_path:string,duration_seconds:float}
     */
    public function finalizeFromUrl(
        string $requestId,
        string $versionId,
        string $sourceUrl,
        string $logoPath,
    ): array {
        $this->assertBinariesAvailable();
        $this->assertAllowedUrl($sourceUrl);

        $baseRoot = $this->workingDirectory ?: storage_path('app/marketing/media');
        $dir = rtrim($baseRoot, '/').'/'.$this->safe($requestId).'/'.$this->safe($versionId);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('video_finalization_workdir_unavailable');
        }

        $basePath = $dir.'/base.mp4';
        $this->download($sourceUrl, $basePath);

        $realLogo = realpath($logoPath);
        if ($realLogo === false || ! is_file($realLogo) || filesize($realLogo) === 0) {
            throw new RuntimeException('video_finalization_logo_missing');
        }

        $duration = $this->duration($basePath);
        $finalPath = $dir.'/final.mp4';
        $this->renderBranding($basePath, $realLogo, $finalPath, $duration);

        $manifestPath = $dir.'/manifest.json';
        $manifest = [
            'request_id' => $requestId,
            'version_id' => $versionId,
            'status' => 'READY_FOR_APPROVAL',
            'provider_source_url' => $this->redactUrl($sourceUrl),
            'base_path' => $basePath,
            'final_path' => $finalPath,
            'logo_path' => $realLogo,
            'duration_seconds' => $duration,
            'finalization' => [
                'watermark' => 'official_logo_top_right',
                'closing_logo' => 'official_logo_center_last_2_seconds',
            ],
            'created_at' => now()->toIso8601String(),
        ];

        if (file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new RuntimeException('video_finalization_manifest_failed');
        }

        return [
            'request_id' => $requestId,
            'version_id' => $versionId,
            'status' => 'READY_FOR_APPROVAL',
            'base_path' => $basePath,
            'final_path' => $finalPath,
            'manifest_path' => $manifestPath,
            'duration_seconds' => $duration,
        ];
    }

    private function download(string $url, string $target): void
    {
        $response = Http::timeout(180)->retry(2, 750)->get($url);
        if ($response->failed()) {
            throw new RuntimeException('video_finalization_download_failed:'.$response->status());
        }

        if (file_put_contents($target, $response->body()) === false || ! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('video_finalization_ingest_failed');
        }
    }

    private function renderBranding(string $videoPath, string $logoPath, string $outputPath, float $duration): void
    {
        $closingStart = max(0.0, $duration - 2.0);
        $filter = sprintf(
            '[1:v]split=2[wm_src][end_src];'.
            '[wm_src]scale=220:-1[wm];'.
            '[end_src]scale=520:-1[end];'.
            '[0:v][wm]overlay=W-w-48:48:format=auto[with_wm];'.
            '[with_wm][end]overlay=(W-w)/2:(H-h)/2:enable=\'gte(t,%.3f)\':format=auto[outv]',
            $closingStart,
        );

        $command = sprintf(
            '%s -hide_banner -loglevel error -y -i %s -loop 1 -i %s -filter_complex %s -map "[outv]" -map 0:a? -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p -c:a aac -b:a 192k -movflags +faststart -shortest %s 2>&1',
            escapeshellarg($this->ffmpegBinary),
            escapeshellarg($videoPath),
            escapeshellarg($logoPath),
            escapeshellarg($filter),
            escapeshellarg($outputPath),
        );

        exec($command, $stdout, $exitCode);
        if ($exitCode !== 0 || ! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('video_finalization_ffmpeg_failed:'.implode(' ', array_slice($stdout, -4)));
        }
    }

    private function duration(string $videoPath): float
    {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($this->ffprobeBinary),
            escapeshellarg($videoPath),
        );
        exec($command, $stdout, $exitCode);

        $duration = isset($stdout[0]) ? (float) trim($stdout[0]) : 0.0;
        if ($exitCode !== 0 || $duration <= 0.0) {
            throw new RuntimeException('video_finalization_duration_unavailable');
        }

        return $duration;
    }

    private function assertAllowedUrl(string $url): void
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') {
            throw new RuntimeException('video_finalization_https_required');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        foreach ($this->allowedHosts as $allowed) {
            $allowed = strtolower($allowed);
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return;
            }
        }

        throw new RuntimeException('video_finalization_host_not_allowed:'.$host);
    }

    private function assertBinariesAvailable(): void
    {
        foreach ([$this->ffmpegBinary, $this->ffprobeBinary] as $binary) {
            $command = sprintf('%s -version 2>/dev/null', escapeshellarg($binary));
            exec($command, $stdout, $exitCode);
            if ($exitCode !== 0 || $stdout === []) {
                throw new RuntimeException('video_finalization_binary_missing:'.$binary);
            }
        }
    }

    private function redactUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '[invalid-url]';
        }

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
    }

    private function safe(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $value), '-');
    }
}
