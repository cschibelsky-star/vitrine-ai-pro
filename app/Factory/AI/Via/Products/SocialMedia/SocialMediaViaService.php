<?php

namespace App\Factory\AI\Via\Products\SocialMedia;

use App\Factory\AI\Via\Services\ViaOrchestrator;

class SocialMediaViaService
{
    public function __construct(
        private readonly ViaOrchestrator $via,
    ) {
    }

    public function generate(array $briefing, array $context = []): array
    {
        $request = [
            'task' => 'social_media_content_generation',
            'briefing' => [
                'objective' => $briefing['objective'] ?? null,
                'audience' => $briefing['audience'] ?? null,
                'channel' => $briefing['channel'] ?? 'instagram',
                'format' => $briefing['format'] ?? 'post',
                'tone' => $briefing['tone'] ?? 'professional',
                'topic' => $briefing['topic'] ?? null,
                'call_to_action' => $briefing['call_to_action'] ?? null,
                'additional_instructions' => $briefing['additional_instructions'] ?? null,
            ],
            'output' => [
                'language' => $briefing['language'] ?? 'pt-BR',
                'include_caption' => true,
                'include_hashtags' => (bool) ($briefing['include_hashtags'] ?? true),
                'include_image_prompt' => (bool) ($briefing['include_image_prompt'] ?? true),
            ],
        ];

        return $this->via->handle($request, array_merge($context, [
            'product' => 'social-midia-ia',
            'metadata' => array_merge(
                $context['metadata'] ?? [],
                [
                    'module' => 'social-media',
                    'channel' => $request['briefing']['channel'],
                    'format' => $request['briefing']['format'],
                ],
            ),
        ]));
    }
}
