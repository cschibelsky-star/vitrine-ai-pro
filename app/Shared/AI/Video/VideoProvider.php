<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

interface VideoProvider
{
    public function providerId(): string;

    public function supports(VideoRequest $request): bool;

    public function generate(VideoRequest $request, VideoSession $session): VideoVersion;
}
