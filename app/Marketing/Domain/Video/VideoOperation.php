<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Video;

enum VideoOperation: string
{
    case CreateVideo = 'CREATE_VIDEO';
    case EditVideo = 'EDIT_VIDEO';
    case RegenerateScene = 'REGENERATE_SCENE';
}
