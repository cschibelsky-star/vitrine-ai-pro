<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Video;

use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use App\Shared\AI\Media\HeygenService;
use App\Shared\AI\Media\Models\HeygenVideoJob;
use InvalidArgumentException;

final class HeygenIncrementalSceneRenderer implements VideoSceneRenderer
{
    public function __construct(private HeygenService $heygen) {}

    public function dispatch(VideoProject $project, VideoScene $scene, array $context = []): array
    {
        $script = trim((string) ($scene->content()['script'] ?? $scene->content()['text'] ?? ''));
        if ($script === '') {
            throw new InvalidArgumentException('video_scene_script_required:'.$scene->sceneId);
        }

        $job = new HeygenVideoJob();
        $job->forceFill([
            'company_id' => $context['company_id'] ?? null,
            'heygen_avatar_id' => $context['heygen_avatar_id'] ?? null,
            'title' => sprintf('%s · %s · v%d', $project->projectId, $scene->sceneId, $scene->version()),
            'script' => $script,
            'status' => 'Preparando',
        ]);
        $job->save();

        $job = $this->heygen->generateVideo($job);

        return $this->result($job);
    }

    public function refresh(string $jobRef): array
    {
        $job = HeygenVideoJob::query()->findOrFail($jobRef);
        $job = $this->heygen->refreshStatus($job);

        return $this->result($job);
    }

    /** @return array{provider:string,status:string,job_ref:string,render_ref:?string} */
    private function result(HeygenVideoJob $job): array
    {
        $status = match ($job->status) {
            'Concluído' => 'completed',
            'Erro' => 'failed',
            default => 'processing',
        };

        return [
            'provider' => 'heygen',
            'status' => $status,
            'job_ref' => (string) $job->getKey(),
            'render_ref' => $status === 'completed' ? (string) $job->video_url : null,
        ];
    }
}
