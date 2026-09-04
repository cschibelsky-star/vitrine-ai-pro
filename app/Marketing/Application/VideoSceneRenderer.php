<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;

interface VideoSceneRenderer
{
    /**
     * @param array<string, mixed> $context
     * @return array{provider:string,status:string,job_ref:string,render_ref:?string}
     */
    public function dispatch(VideoProject $project, VideoScene $scene, array $context = []): array;

    /**
     * @return array{provider:string,status:string,job_ref:string,render_ref:?string}
     */
    public function refresh(string $jobRef): array;
}
