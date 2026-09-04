<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;

class ProductRequestResolver
{
    public function __construct(
        protected AdvancedRequirementAnalyzer $analyzer,
    ) {
    }

    public function resolve(string $request): array
    {
        $blueprint = $this->analyzer->analyze($request);
        $domain = (string) ($blueprint['architecture']['domain'] ?? 'generico');

        $product = match ($domain) {
            'turismo' => 'guia_digital',
            'saude' => 'sismed',
            'portal_news' => 'portal_news',
            'tv_digital' => 'tv_digital',
            'licitacoes', 'fornecedores', 'governo_digital' => 'gov360',
            default => null,
        };

        return [
            'request' => $request,
            'domain' => $domain,
            'blueprint' => $blueprint,
            'resolved_product' => $product,
            'catalog_match' => $product !== null,
            'resolution_source' => 'FACTORY_INTELLIGENCE_CORE',
            'resolved_at' => now()->toISOString(),
        ];
    }
}
