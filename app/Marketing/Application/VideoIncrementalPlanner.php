<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Video\RenderVersion;
use App\Marketing\Domain\Video\VideoOperation;
use App\Marketing\Domain\Video\VideoProject;
use InvalidArgumentException;

final class VideoIncrementalPlanner
{
    /**
     * @param array<string, array<string, mixed>> $sceneChanges keyed by scene id
     * @return array{operation:string,rerender_scene_ids:list<string>,reused_scene_refs:array<string,string>,next_render_version:int}
     */
    public function plan(VideoProject $project, VideoOperation $operation, array $sceneChanges = []): array
    {
        if ($operation === VideoOperation::CreateVideo) {
            return [
                'operation' => $operation->value,
                'rerender_scene_ids' => array_map(fn ($scene): string => $scene->sceneId, $project->scenes()),
                'reused_scene_refs' => [],
                'next_render_version' => count($project->renders()) + 1,
            ];
        }

        if ($operation === VideoOperation::EditVideo && $sceneChanges === []) {
            throw new InvalidArgumentException('video_edit_changes_required');
        }

        if ($operation === VideoOperation::RegenerateScene && count($sceneChanges) !== 1) {
            throw new InvalidArgumentException('single_scene_required_for_regeneration');
        }

        foreach ($sceneChanges as $sceneId => $changes) {
            $scene = $project->scene($sceneId);
            if ($operation === VideoOperation::RegenerateScene) {
                $scene->invalidate();
                continue;
            }

            $scene->edit($changes);
        }

        $rerender = [];
        $reused = [];

        foreach ($project->scenes() as $scene) {
            if ($scene->isDirty()) {
                $rerender[] = $scene->sceneId;
                continue;
            }

            if ($scene->renderRef() !== null) {
                $reused[$scene->sceneId] = $scene->renderRef();
            }
        }

        return [
            'operation' => $operation->value,
            'rerender_scene_ids' => $rerender,
            'reused_scene_refs' => $reused,
            'next_render_version' => count($project->renders()) + 1,
        ];
    }

    /** @param array<string, string> $sceneRenderRefs */
    public function compose(VideoProject $project, VideoOperation $operation, string $outputRef, array $sceneRenderRefs): RenderVersion
    {
        foreach ($project->scenes() as $scene) {
            $ref = $sceneRenderRefs[$scene->sceneId] ?? $scene->renderRef();
            if ($ref === null || $ref === '') {
                throw new InvalidArgumentException('scene_render_missing:'.$scene->sceneId);
            }
            $scene->markRendered($ref);
            $sceneRenderRefs[$scene->sceneId] = $ref;
        }

        $render = new RenderVersion(
            version: count($project->renders()) + 1,
            outputRef: $outputRef,
            sceneRenderRefs: $sceneRenderRefs,
            operation: $operation,
        );
        $project->addRenderVersion($render);

        return $render;
    }
}
