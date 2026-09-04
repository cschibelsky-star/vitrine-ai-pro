<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Video;

use InvalidArgumentException;

final class RenderVersion
{
    /** @param array<string, string> $sceneRenderRefs */
    public function __construct(
        public readonly int $version,
        public readonly string $outputRef,
        public readonly array $sceneRenderRefs,
        public readonly VideoOperation $operation,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException('render_version_invalid');
        }

        if ($outputRef === '') {
            throw new InvalidArgumentException('render_output_ref_required');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'output_ref' => $this->outputRef,
            'scene_render_refs' => $this->sceneRenderRefs,
            'operation' => $this->operation->value,
        ];
    }
}
