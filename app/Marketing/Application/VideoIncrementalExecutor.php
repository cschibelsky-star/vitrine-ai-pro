<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Video\RenderVersion;
use App\Marketing\Domain\Video\VideoOperation;
use App\Marketing\Domain\Video\VideoProject;
use RuntimeException;

final class VideoIncrementalExecutor
{
    public function __construct(
        private VideoIncrementalPlanner $planner,
        private VideoSceneRenderer $renderer,
        private ?VideoClipComposer $composer = null,
    ) {}

    /**
     * @param array<string, array<string, mixed>> $sceneChanges
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function dispatch(VideoProject $project, VideoOperation $operation, array $sceneChanges = [], array $context = []): array
    {
        $plan = $this->planner->plan($project, $operation, $sceneChanges);
        $jobs = [];

        foreach ($plan['rerender_scene_ids'] as $sceneId) {
            $jobs[$sceneId] = $this->renderer->dispatch($project, $project->scene($sceneId), $context);
        }

        return [
            'operation' => $plan['operation'],
            'jobs' => $jobs,
            'reused_scene_refs' => $plan['reused_scene_refs'],
            'next_render_version' => $plan['next_render_version'],
            'ready_to_compose' => $this->allCompleted($jobs),
        ];
    }

    /**
     * @param array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}> $jobs
     * @return array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}>
     */
    public function refresh(array $jobs): array
    {
        foreach ($jobs as $sceneId => $job) {
            if ($job['status'] === 'completed') {
                continue;
            }
            $jobs[$sceneId] = $this->renderer->refresh($job['job_ref']);
        }

        return $jobs;
    }

    /**
     * Composes all scene refs in project order, reusing unchanged clips and replacing only rerendered scenes.
     *
     * @param array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}> $jobs
     */
    public function composeAndFinalize(VideoProject $project, VideoOperation $operation, array $jobs): RenderVersion
    {
        if ($this->composer === null) {
            throw new RuntimeException('video_composer_not_configured');
        }
        if (! $this->allCompleted($jobs)) {
            throw new RuntimeException('video_scenes_still_processing');
        }

        $refs = $this->sceneRefs($project, $jobs);
        $ordered = [];
        foreach ($project->scenes() as $scene) {
            $ordered[] = $refs[$scene->sceneId];
        }

        $version = count($project->renders()) + 1;
        $outputRef = $this->composer->compose($project, $ordered, $version);

        return $this->planner->compose($project, $operation, $outputRef, $refs);
    }

    /**
     * Backward-compatible finalization when composition is provided by another trusted service.
     *
     * @param array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}> $jobs
     */
    public function finalize(VideoProject $project, VideoOperation $operation, array $jobs, string $outputRef): RenderVersion
    {
        if (! $this->allCompleted($jobs)) {
            throw new RuntimeException('video_scenes_still_processing');
        }

        return $this->planner->compose($project, $operation, $outputRef, $this->sceneRefs($project, $jobs));
    }

    /**
     * @param array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}> $jobs
     * @return array<string,string>
     */
    private function sceneRefs(VideoProject $project, array $jobs): array
    {
        $refs = [];
        foreach ($project->scenes() as $scene) {
            $job = $jobs[$scene->sceneId] ?? null;
            $ref = $job['render_ref'] ?? $scene->renderRef();
            if (! is_string($ref) || $ref === '') {
                throw new RuntimeException('video_scene_render_missing:'.$scene->sceneId);
            }
            $refs[$scene->sceneId] = $ref;
        }

        return $refs;
    }

    /** @param array<string, array{provider:string,status:string,job_ref:string,render_ref:?string}> $jobs */
    private function allCompleted(array $jobs): bool
    {
        foreach ($jobs as $job) {
            if ($job['status'] !== 'completed' || ! is_string($job['render_ref']) || $job['render_ref'] === '') {
                return false;
            }
        }

        return true;
    }
}
