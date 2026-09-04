<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Video;

use InvalidArgumentException;

final class VideoProject
{
    /** @var array<string, VideoScene> */
    private array $scenes = [];

    /** @var list<RenderVersion> */
    private array $renders = [];

    public function __construct(
        public readonly string $projectId,
        public readonly string $productId,
        public readonly string $campaignId,
    ) {
        if ($projectId === '' || $productId === '' || $campaignId === '') {
            throw new InvalidArgumentException('video_project_identity_required');
        }
    }

    /** @param array<string, mixed> $content */
    public function addScene(string $sceneId, int $position, array $content): VideoScene
    {
        if (isset($this->scenes[$sceneId])) {
            throw new InvalidArgumentException('video_scene_already_exists');
        }

        $scene = new VideoScene($sceneId, $position, $content);
        $this->scenes[$sceneId] = $scene;

        return $scene;
    }

    public function scene(string $sceneId): VideoScene
    {
        if (! isset($this->scenes[$sceneId])) {
            throw new InvalidArgumentException('video_scene_not_found');
        }

        return $this->scenes[$sceneId];
    }

    /** @return list<VideoScene> */
    public function scenes(): array
    {
        $scenes = array_values($this->scenes);
        usort($scenes, fn (VideoScene $a, VideoScene $b): int => $a->position <=> $b->position);

        return $scenes;
    }

    /** @return list<VideoScene> */
    public function dirtyScenes(): array
    {
        return array_values(array_filter(
            $this->scenes(),
            fn (VideoScene $scene): bool => $scene->isDirty(),
        ));
    }

    public function addRenderVersion(RenderVersion $render): void
    {
        $this->renders[] = $render;
    }

    public function latestRender(): ?RenderVersion
    {
        return $this->renders === [] ? null : $this->renders[array_key_last($this->renders)];
    }

    /** @return list<RenderVersion> */
    public function renders(): array
    {
        return $this->renders;
    }
}
