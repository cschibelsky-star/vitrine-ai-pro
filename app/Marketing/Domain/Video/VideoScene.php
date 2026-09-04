<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Video;

use InvalidArgumentException;

final class VideoScene
{
    /** @param array<string, mixed> $content */
    public function __construct(
        public readonly string $sceneId,
        public readonly int $position,
        private array $content,
        private int $version = 1,
        private bool $dirty = true,
        private ?string $renderRef = null,
    ) {
        if ($sceneId === '') {
            throw new InvalidArgumentException('scene_id_required');
        }

        if ($position < 1) {
            throw new InvalidArgumentException('scene_position_invalid');
        }
    }

    /** @param array<string, mixed> $changes */
    public function edit(array $changes): bool
    {
        $next = array_replace_recursive($this->content, $changes);

        if ($next === $this->content) {
            return false;
        }

        $this->content = $next;
        $this->version++;
        $this->dirty = true;
        $this->renderRef = null;

        return true;
    }

    public function invalidate(): void
    {
        $this->version++;
        $this->dirty = true;
        $this->renderRef = null;
    }

    public function markRendered(string $renderRef): void
    {
        if ($renderRef === '') {
            throw new InvalidArgumentException('scene_render_ref_required');
        }

        $this->renderRef = $renderRef;
        $this->dirty = false;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function renderRef(): ?string
    {
        return $this->renderRef;
    }

    /** @return array<string, mixed> */
    public function content(): array
    {
        return $this->content;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'scene_id' => $this->sceneId,
            'position' => $this->position,
            'version' => $this->version,
            'dirty' => $this->dirty,
            'render_ref' => $this->renderRef,
            'content' => $this->content,
        ];
    }
}
