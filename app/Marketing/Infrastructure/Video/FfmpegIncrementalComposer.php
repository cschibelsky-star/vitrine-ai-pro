<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Video;

use App\Marketing\Application\VideoClipComposer;
use App\Marketing\Domain\Video\VideoProject;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FfmpegIncrementalComposer implements VideoClipComposer
{
    /** @param list<string> $allowedHosts */
    public function __construct(
        private readonly string $ffmpegBinary = 'ffmpeg',
        private readonly ?string $workingDirectory = null,
        private readonly array $allowedHosts = ['resource.heygen.ai', 'files2.heygen.ai', 'video.heygen.com'],
    ) {}

    public function compose(VideoProject $project, array $orderedClipRefs, int $version): string
    {
        if ($orderedClipRefs === []) {
            throw new RuntimeException('video_compose_clips_required');
        }

        $this->assertFfmpegAvailable();

        $base = $this->workingDirectory ?: storage_path('app/marketing/video');
        $dir = rtrim($base, '/').'/'.$this->safe($project->projectId).'/v'.$version;
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('video_compose_workdir_unavailable');
        }

        $clips = [];
        foreach (array_values($orderedClipRefs) as $index => $ref) {
            $clips[] = $this->materialize($ref, $dir, $index + 1);
        }

        $manifest = $dir.'/concat.txt';
        $lines = array_map(
            fn (string $path): string => "file '".str_replace("'", "'\\''", $path)."'",
            $clips,
        );
        if (file_put_contents($manifest, implode(PHP_EOL, $lines).PHP_EOL) === false) {
            throw new RuntimeException('video_compose_manifest_failed');
        }

        $output = $dir.'/video-v'.$version.'.mp4';
        $command = sprintf(
            '%s -hide_banner -loglevel error -y -f concat -safe 0 -i %s -c copy -movflags +faststart %s 2>&1',
            escapeshellarg($this->ffmpegBinary),
            escapeshellarg($manifest),
            escapeshellarg($output),
        );
        exec($command, $stdout, $exitCode);

        if ($exitCode !== 0 || ! is_file($output) || filesize($output) === 0) {
            throw new RuntimeException('video_compose_ffmpeg_failed:'.implode(' ', array_slice($stdout, -3)));
        }

        return $output;
    }

    private function assertFfmpegAvailable(): void
    {
        $command = sprintf('%s -version 2>/dev/null', escapeshellarg($this->ffmpegBinary));
        exec($command, $stdout, $exitCode);
        if ($exitCode !== 0 || $stdout === []) {
            throw new RuntimeException('ffmpeg_not_available');
        }
    }

    private function materialize(string $ref, string $dir, int $position): string
    {
        if (filter_var($ref, FILTER_VALIDATE_URL)) {
            $parts = parse_url($ref);
            if (($parts['scheme'] ?? '') !== 'https') {
                throw new RuntimeException('video_clip_https_required');
            }
            $host = strtolower((string) ($parts['host'] ?? ''));
            if (! $this->hostAllowed($host)) {
                throw new RuntimeException('video_clip_host_not_allowed:'.$host);
            }

            $target = sprintf('%s/scene-%03d-%s.mp4', $dir, $position, hash('sha256', $ref));
            if (! is_file($target) || filesize($target) === 0) {
                $response = Http::timeout(120)->retry(2, 500)->get($ref);
                if ($response->failed()) {
                    throw new RuntimeException('video_clip_download_failed:'.$response->status());
                }
                if (file_put_contents($target, $response->body()) === false || filesize($target) === 0) {
                    throw new RuntimeException('video_clip_cache_failed');
                }
            }

            return $target;
        }

        $real = realpath($ref);
        if ($real === false || ! is_file($real)) {
            throw new RuntimeException('video_clip_not_found');
        }

        return $real;
    }

    private function hostAllowed(string $host): bool
    {
        foreach ($this->allowedHosts as $allowed) {
            $allowed = strtolower($allowed);
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }
        return false;
    }

    private function safe(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $value), '-');
    }
}
