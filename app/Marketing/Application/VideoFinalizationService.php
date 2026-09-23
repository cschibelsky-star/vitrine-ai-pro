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
     * @return array{request_id:string,version_id:string,status:string,base_path:string,final_path:string,manifest_path:string,duration_seconds:float}
     */
    public function finalizeFromUrl(string $requestId, string $versionId, string $sourceUrl, string $logoPath): array
    {
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

    /**
     * Build a lightweight 9:16 news Reel from a TV Sumare article image.
     *
     * @return array{slug:string,path:string,duration_seconds:float}
     */
    public function createTvSumareArticleReel(string $slug, string $imageUrl, string $title, string $summary = ''): array
    {
        $this->assertBinariesAvailable();

        $slug = $this->safe($slug);
        if ($slug === '') {
            throw new RuntimeException('tv_sumare_reel_slug_invalid');
        }

        $parts = parse_url($imageUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || ! in_array($host, ['tvsumare.com.br', 'www.tvsumare.com.br'], true)) {
            throw new RuntimeException('tv_sumare_reel_image_host_not_allowed');
        }

        $dir = storage_path('app/marketing/tv-sumare-reels');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('tv_sumare_reel_workdir_unavailable');
        }

        $imagePath = $dir.'/'.$slug.'.jpg';
        $videoPath = $dir.'/'.$slug.'.mp4';

        $response = Http::timeout(90)->retry(2, 500)->get($imageUrl);
        if ($response->failed()) {
            throw new RuntimeException('tv_sumare_reel_image_download_failed:'.$response->status());
        }
        if (file_put_contents($imagePath, $response->body()) === false || ! is_file($imagePath) || filesize($imagePath) === 0) {
            throw new RuntimeException('tv_sumare_reel_image_write_failed');
        }

        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        if (! is_file($font)) {
            $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        }
        if (! is_file($font)) {
            throw new RuntimeException('tv_sumare_reel_font_missing');
        }

        $titleText = $this->ffmpegText(wordwrap(trim($title), 28, "\n", true));
        $summaryText = $this->ffmpegText(wordwrap(trim($summary), 40, "\n", true));
        $brandText = $this->ffmpegText('TV SUMARE');
        $ctaText = $this->ffmpegText('Leia a materia completa em tvsumare.com.br');

        $filters = [
            "[0:v]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,boxblur=18:8[bg]",
            "[0:v]scale=980:980:force_original_aspect_ratio=decrease[photo]",
            "[bg]drawbox=x=0:y=0:w=1080:h=1920:color=black@0.48:t=fill[dark]",
            "[dark][photo]overlay=(W-w)/2:245[base]",
            "[base]drawtext=fontfile='".$font."':text='".$brandText."':fontcolor=white:fontsize=54:x=70:y=90[v1]",
            "[v1]drawbox=x=60:y=1280:w=960:h=470:color=black@0.62:t=fill[v2]",
            "[v2]drawtext=fontfile='".$font."':text='".$titleText."':fontcolor=white:fontsize=54:line_spacing=10:x=90:y=1320[v3]",
        ];
        if ($summaryText !== '') {
            $filters[] = "[v3]drawtext=fontfile='".$font."':text='".$summaryText."':fontcolor=white@0.92:fontsize=32:line_spacing=8:x=90:y=1545[v4]";
            $last = 'v4';
        } else {
            $last = 'v3';
        }
        $filters[] = "[{$last}]drawtext=fontfile='".$font."':text='".$ctaText."':fontcolor=white:fontsize=30:x=(w-text_w)/2:y=1810[outv]";

        $command = sprintf(
            '%s -hide_banner -loglevel error -y -loop 1 -framerate 30 -i %s -filter_complex %s -map "[outv]" -t 15 -r 30 -c:v libx264 -preset medium -crf 20 -pix_fmt yuv420p -movflags +faststart %s 2>&1',
            escapeshellarg($this->ffmpegBinary),
            escapeshellarg($imagePath),
            escapeshellarg(implode(';', $filters)),
            escapeshellarg($videoPath),
        );

        exec($command, $stdout, $exitCode);
        @unlink($imagePath);

        if ($exitCode !== 0 || ! is_file($videoPath) || filesize($videoPath) === 0) {
            throw new RuntimeException('tv_sumare_reel_ffmpeg_failed:'.implode(' ', array_slice($stdout, -6)));
        }

        return [
            'slug' => $slug,
            'path' => $videoPath,
            'duration_seconds' => 15.0,
        ];
    }

    private function ffmpegText(string $value): string
    {
        return str_replace(
            ['\\', ':', "'", '%', "\r"],
            ['\\\\', '\\:', "\\'", '\\%', ''],
            $value,
        );
    }

    private function download(string $url, string $target): void
    {
        $request = Http::timeout(180)->retry(2, 750);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === 'generativelanguage.googleapis.com' || str_ends_with($host, '.generativelanguage.googleapis.com')) {
            $apiKey = trim((string) config('marketing_video.gemini_veo.api_key'));
            if ($apiKey === '') {
                throw new RuntimeException('video_finalization_gemini_key_missing');
            }
            $request = $request->withHeaders(['x-goog-api-key' => $apiKey]);
        }

        $response = $request->get($url);
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
