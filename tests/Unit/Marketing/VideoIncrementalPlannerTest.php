<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\VideoIncrementalPlanner;
use App\Marketing\Domain\Video\AssetCache;
use App\Marketing\Domain\Video\VideoOperation;
use App\Marketing\Domain\Video\VideoProject;
use Tests\TestCase;

final class VideoIncrementalPlannerTest extends TestCase
{
    public function test_create_video_renders_all_scenes_and_creates_first_version(): void
    {
        $project = $this->projectWithThreeScenes();
        $planner = new VideoIncrementalPlanner();

        $plan = $planner->plan($project, VideoOperation::CreateVideo);

        $this->assertSame(['scene-1', 'scene-2', 'scene-3'], $plan['rerender_scene_ids']);
        $this->assertSame([], $plan['reused_scene_refs']);
        $this->assertSame(1, $plan['next_render_version']);

        $render = $planner->compose($project, VideoOperation::CreateVideo, 'video://v1', [
            'scene-1' => 'scene://1-v1',
            'scene-2' => 'scene://2-v1',
            'scene-3' => 'scene://3-v1',
        ]);

        $this->assertSame(1, $render->version);
        $this->assertSame('video://v1', $render->outputRef);
        $this->assertCount(0, $project->dirtyScenes());
    }

    public function test_edit_video_rerenders_only_changed_scene_and_reuses_others(): void
    {
        $project = $this->renderedProject();
        $planner = new VideoIncrementalPlanner();

        $plan = $planner->plan($project, VideoOperation::EditVideo, [
            'scene-2' => ['cta' => 'Agende sua demonstração agora'],
        ]);

        $this->assertSame(['scene-2'], $plan['rerender_scene_ids']);
        $this->assertSame([
            'scene-1' => 'scene://1-v1',
            'scene-3' => 'scene://3-v1',
        ], $plan['reused_scene_refs']);
        $this->assertSame(2, $plan['next_render_version']);
        $this->assertSame(2, $project->scene('scene-2')->version());
        $this->assertNull($project->scene('scene-2')->renderRef());

        $render = $planner->compose($project, VideoOperation::EditVideo, 'video://v2', [
            'scene-2' => 'scene://2-v2',
        ]);

        $this->assertSame(2, $render->version);
        $this->assertSame('scene://1-v1', $render->sceneRenderRefs['scene-1']);
        $this->assertSame('scene://2-v2', $render->sceneRenderRefs['scene-2']);
        $this->assertSame('scene://3-v1', $render->sceneRenderRefs['scene-3']);
        $this->assertCount(0, $project->dirtyScenes());
    }

    public function test_regenerate_scene_invalidates_only_target_scene(): void
    {
        $project = $this->renderedProject();
        $planner = new VideoIncrementalPlanner();

        $plan = $planner->plan($project, VideoOperation::RegenerateScene, [
            'scene-3' => [],
        ]);

        $this->assertSame(['scene-3'], $plan['rerender_scene_ids']);
        $this->assertSame('scene://1-v1', $plan['reused_scene_refs']['scene-1']);
        $this->assertSame('scene://2-v1', $plan['reused_scene_refs']['scene-2']);
        $this->assertNull($project->scene('scene-3')->renderRef());
        $this->assertSame(2, $project->scene('scene-3')->version());
    }

    public function test_asset_cache_reuses_identical_generated_assets(): void
    {
        $cache = new AssetCache();
        $cache->put('voice:hash-123', 'asset://voice-1', ['provider' => 'heygen']);

        $this->assertTrue($cache->has('voice:hash-123'));
        $this->assertSame('asset://voice-1', $cache->get('voice:hash-123')['ref']);
        $this->assertSame('heygen', $cache->get('voice:hash-123')['metadata']['provider']);
    }

    private function renderedProject(): VideoProject
    {
        $project = $this->projectWithThreeScenes();
        $planner = new VideoIncrementalPlanner();
        $planner->compose($project, VideoOperation::CreateVideo, 'video://v1', [
            'scene-1' => 'scene://1-v1',
            'scene-2' => 'scene://2-v1',
            'scene-3' => 'scene://3-v1',
        ]);

        return $project;
    }

    private function projectWithThreeScenes(): VideoProject
    {
        $project = new VideoProject('video-project-1', 'vitrine-social-midia', 'campaign-1');
        $project->addScene('scene-1', 1, ['text' => 'Abertura']);
        $project->addScene('scene-2', 2, ['text' => 'Benefício', 'cta' => 'Conheça']);
        $project->addScene('scene-3', 3, ['text' => 'Fechamento']);

        return $project;
    }
}
