<?php

namespace App\Factory\AI\Via\Products\TvDigital;

use App\Factory\AI\Via\Services\ViaOrchestrator;
use InvalidArgumentException;

class TvDigitalViaService
{
    private const SUPPORTED_TASKS = [
        'article_expansion',
        'article_summary',
        'headline_generation',
        'seo_enrichment',
        'video_script_generation',
        'content_classification',
    ];

    public function __construct(
        private readonly ViaOrchestrator $via,
    ) {
    }

    public function execute(string $task, array $input, array $context = []): array
    {
        if (! in_array($task, self::SUPPORTED_TASKS, true)) {
            throw new InvalidArgumentException("Unsupported TV Digital task: {$task}");
        }

        $request = [
            'task' => $task,
            'domain' => 'digital_newsroom',
            'input' => [
                'title' => $input['title'] ?? null,
                'source_text' => $input['source_text'] ?? null,
                'source_url' => $input['source_url'] ?? null,
                'source_name' => $input['source_name'] ?? null,
                'category' => $input['category'] ?? null,
                'city' => $input['city'] ?? null,
                'region' => $input['region'] ?? null,
                'related_video_url' => $input['related_video_url'] ?? null,
                'image_url' => $input['image_url'] ?? null,
                'additional_context' => $input['additional_context'] ?? null,
            ],
            'editorial_rules' => [
                'preserve_facts' => true,
                'do_not_invent_quotes' => true,
                'do_not_invent_sources' => true,
                'avoid_duplicate_content' => true,
                'prioritize_local_relevance' => (bool) ($input['prioritize_local_relevance'] ?? true),
                'require_human_review_before_publish' => (bool) ($input['require_human_review_before_publish'] ?? true),
            ],
            'output' => [
                'language' => $input['language'] ?? 'pt-BR',
                'include_title' => (bool) ($input['include_title'] ?? true),
                'include_summary' => (bool) ($input['include_summary'] ?? true),
                'include_body' => (bool) ($input['include_body'] ?? true),
                'include_seo' => (bool) ($input['include_seo'] ?? true),
                'include_tags' => (bool) ($input['include_tags'] ?? true),
                'include_image_guidance' => (bool) ($input['include_image_guidance'] ?? true),
            ],
        ];

        return $this->via->handle($request, array_merge($context, [
            'product' => 'tv-digital-enterprise',
            'metadata' => array_merge(
                $context['metadata'] ?? [],
                [
                    'module' => 'tv-digital',
                    'task' => $task,
                    'category' => $request['input']['category'],
                    'city' => $request['input']['city'],
                    'source_name' => $request['input']['source_name'],
                ],
            ),
        ]));
    }

    public function expandArticle(array $input, array $context = []): array
    {
        return $this->execute('article_expansion', $input, $context);
    }

    public function summarizeArticle(array $input, array $context = []): array
    {
        return $this->execute('article_summary', $input, $context);
    }

    public function generateVideoScript(array $input, array $context = []): array
    {
        return $this->execute('video_script_generation', $input, $context);
    }
}
