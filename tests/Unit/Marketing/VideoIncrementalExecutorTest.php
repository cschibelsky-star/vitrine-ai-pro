<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\VideoIncrementalExecutor;
use App\Marketing\Application\VideoIncrementalPlanner;
use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Domain\Video\VideoOperation;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use PHPUnit\Framework\TestCase;

final class VideoIncrementalExecutorTest extends TestCase
{
    public function test_edit_dispatches_only_changed_scene_and_reuses_existing_renders(): void
    {
        $project = $this->renderedProject();
        $renderer = new RecordingSceneRenderer();
        $executor = new VideoIncrementalExecutor(new VideoIncrementalPlanner(), $renderer);

        $result = $executor->dispatch($project, VideoOperation::EditVideo, [
            'scene-2' => ['script' => 'CTA atualizado'],
        ]);

        $this->assertSame(['scene-2'], $renderer->dispatchedSceneIds);
        $this->assertSame([
            'scene-1' => 'https://cdn.test/scene-1-v1.mp4',
            'scene-3' => 'https://cdn.test/scene-3-v1.mp4',
        ], $result['reused_scene_refs']);
        $this->assertArrayHasKey('scene-2', $result['jobs']);
        $this->assertFalse($result['ready_to_compose']);
        $this->assertSame(2, $project->scene('scene-2')->version());
        $this->assertSame(1, $project->scene('scene-1')->version());
    }

    public function test_completed_dirty_scene_can_finalize_new_version_without_rerendering_clean_scenes(): void
    {
        $project = $this->renderedProject();
        $renderer = new RecordingSceneRenderer(completed: true);
        $executor = new VideoIncrementalExecutor(new VideoIncrementalPlanner(), $renderer);

        $result = $executor->dispatch($project, VideoOperation::RegenerateScene, [
            'scene-2' => [],
        ]);

        $this->assertTrue($result['ready_to_compose']);
        $render = $executor->finalize(
            $project,
            VideoOperation::RegenerateScene,
            $result['jobs'],
            'https://cdn.test/video-v2.mp4',
        );

        $this->assertSame(2, $render->version);
        $this->assertSame('https://cdn.test/scene-1-v1.mp4', $render->sceneRenderRefs['scene-1']);
        $this->assertSame('https://cdn.test/scene-2-v2.mp4', $render->sceneRenderRefs['scene-2']);
        $this->assertSame('https://cdn.test/scene-3-v1.mp4', $render->sceneRenderRefs['scene-3']);
        $this->assertSame(['scene-2'], $renderer->dispatchedSceneIds);
    }

    private function renderedProject(): VideoProject
    {
        $project = new VideoProject('video-1', 'product-1', 'campaign-1');
        foreach ([1, 2, 3] as $position) {
            $scene = $project->addScene('scene-'.$position, $position, ['script' => 'Cena '.$position]);
            $scene->markRendered('https://cdn.test/scene-'.$position.'-v1.mp4');
        }

        (new VideoIncrementalPlanner())->compose($project, VideoOperation::CreateVideo, 'https://cdn.test/video-v1.mp4', []);

        return $project;
    }
}

final class RecordingSceneRenderer implements VideoSceneRenderer
{
    /** @var list<string> */
    public array $dispatchedSceneIds = [];

    public function __construct(private bool $completed = false) {}

    public function dispatch(VideoProject $project, VideoScene $scene, array $context = []): array
    {
        $this->dispatchedSceneIds[] = $scene->sceneId;

        return [
            'provider' => 'heygen',
            'status' => $this->completed ? 'completed' : 'processing',
            'job_ref' => 'job-'.$scene->sceneId.'-v'.$scene->version(),
            'render_ref' => $this->completed ? 'https://cdn.test/'.$scene->sceneId.'-v'.$scene->version().'.mp4' : null,
        ];
    }

    public function refresh(string $jobRef): array
    {
        return [
            'provider' => 'heygen',
            'status' => 'completed',
            'job_ref' => $jobRef,
            'render_ref' => 'https://cdn.test/refreshed.mp4',
        ];
    }
}
