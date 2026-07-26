<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use Illuminate\Support\Str;

class ProductRequestResolver
{
    public function resolve(string $request): array
    {
        $text = Str::of($request)->lower()->ascii()->toString();

        $scores = [
            'gov360' => 0,
            'guia_digital' => 0,
            'portal_news' => 0,
            'tv_digital' => 0,
            'sismed' => 0,
        ];

        foreach (['governo','vender','licitacao','licitacoes','empresa','fornecedor','compras'] as $word) {
            if (str_contains($text, $word)) $scores['gov360'] += 2;
        }

        foreach (['turismo','cidade','guia','evento','atrativo','roteiro'] as $word) {
            if (str_contains($text, $word)) $scores['guia_digital'] += 2;
        }

        foreach (['portal','noticia','noticias','rss','jornal'] as $word) {
            if (str_contains($text, $word)) $scores['portal_news'] += 2;
        }

        foreach (['tv','video','videos','ao vivo','playlist'] as $word) {
            if (str_contains($text, $word)) $scores['tv_digital'] += 2;
        }

        foreach (['saude','paciente','atendimento','unidade','sismed'] as $word) {
            if (str_contains($text, $word)) $scores['sismed'] += 2;
        }

        arsort($scores);

        $product = array_key_first($scores);
        $confidence = (int) current($scores);

        if ($confidence <= 0) {
            $product = 'gov360';
            $confidence = 1;
        }

        $via = $this->resolveViaDestination($product, $text);

        return [
            'request' => $request,
            'resolved_product' => $product,
            'confidence_score' => $confidence,
            'scores' => $scores,
            'via_product' => $via['product'],
            'via_task' => $via['task'],
            'via_ready' => $via['product'] !== null && $via['task'] !== null,
            'resolved_at' => now()->toISOString(),
        ];
    }

    private function resolveViaDestination(string $product, string $text): array
    {
        if (! in_array($product, ['portal_news', 'tv_digital'], true)) {
            return ['product' => null, 'task' => null];
        }

        $task = match (true) {
            str_contains($text, 'resum') => 'article_summary',
            str_contains($text, 'titulo') || str_contains($text, 'manchete') => 'headline_generation',
            str_contains($text, 'seo') => 'seo_enrichment',
            str_contains($text, 'roteiro') || str_contains($text, 'video') => 'video_script_generation',
            str_contains($text, 'classific') || str_contains($text, 'categoria') => 'content_classification',
            default => 'article_expansion',
        };

        return [
            'product' => 'tv-digital-enterprise',
            'task' => $task,
        ];
    }
}
