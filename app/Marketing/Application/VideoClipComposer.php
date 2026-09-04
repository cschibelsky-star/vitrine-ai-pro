<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Video\VideoProject;

interface VideoClipComposer
{
    /**
     * @param list<string> $orderedClipRefs
     */
    public function compose(VideoProject $project, array $orderedClipRefs, int $version): string;
}
