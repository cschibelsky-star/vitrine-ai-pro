<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Infrastructure\Video\FfmpegIncrementalComposer;
use App\Marketing\Domain\Video\VideoProject;
use PHPUnit\Framework\TestCase;

final class FfmpegIncrementalComposerTest extends TestCase
{
    public function test_real_ffmpeg_composes_two_local_scene_clips_into_non_empty_mp4(): void
    {
        if (! $this->ffmpegAvailable()) {
            $this->markTestSkipped('ffmpeg runtime is not installed in this validation image');
        }

        $root = sys_get_temp_dir().'/marketing-ffmpeg-e2e-'.bin2hex(random_bytes(5));
        mkdir($root, 0775, true);
        $scene1 = $root.'/scene-1.mp4';
        $scene2 = $root.'/scene-2.mp4';

        try {
            $this->makeClip($scene1, 1);
            $this->makeClip($scene2, 2);

            $project = new VideoProject('ffmpeg-e2e', 'product-1', 'campaign-1');
            $project->addScene('scene-1', 1, ['script' => 'Cena 1'])->markRendered($scene1);
            $project->addScene('scene-2', 2, ['script' => 'Cena 2'])->markRendered($scene2);

            $composer = new FfmpegIncrementalComposer('ffmpeg', $root.'/work');
            $output = $composer->compose($project, [$scene1, $scene2], 1);

            $this->assertFileExists($output);
            $this->assertGreaterThan(0, filesize($output));
            $this->assertStringEndsWith('/ffmpeg-e2e/v1/video-v1.mp4', $output);
        } finally {
            $this->removeTree($root);
        }
    }

    private function ffmpegAvailable(): bool
    {
        exec('ffmpeg -version >/dev/null 2>&1', $output, $exitCode);
        return $exitCode === 0;
    }

    private function makeClip(string $path, int $frequency): void
    {
        $command = sprintf(
            'ffmpeg -hide_banner -loglevel error -y -f lavfi -i %s -t 0.4 -c:v libx264 -pix_fmt yuv420p %s 2>&1',
            escapeshellarg('color=c=black:s=320x180:r=25'),
            escapeshellarg($path),
        );
        exec($command, $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($path);
    }

    private function removeTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
