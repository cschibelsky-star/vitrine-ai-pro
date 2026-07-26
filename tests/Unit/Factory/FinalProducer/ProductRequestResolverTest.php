<?php

namespace Tests\Unit\Factory\FinalProducer;

use App\Factory\FinalProducer\Services\ProductRequestResolver;
use Tests\TestCase;

class ProductRequestResolverTest extends TestCase
{
    public function test_routes_news_expansion_request_to_tv_digital_via(): void
    {
        $result = app(ProductRequestResolver::class)->resolve(
            'Expandir notícia capturada do portal para publicação editorial.'
        );

        $this->assertSame('portal_news', $result['resolved_product']);
        $this->assertSame('tv-digital-enterprise', $result['via_product']);
        $this->assertSame('article_expansion', $result['via_task']);
        $this->assertTrue($result['via_ready']);
    }

    public function test_routes_video_script_request_to_tv_digital_via(): void
    {
        $result = app(ProductRequestResolver::class)->resolve(
            'Criar roteiro de vídeo para a TV Digital.'
        );

        $this->assertSame('tv_digital', $result['resolved_product']);
        $this->assertSame('tv-digital-enterprise', $result['via_product']);
        $this->assertSame('video_script_generation', $result['via_task']);
        $this->assertTrue($result['via_ready']);
    }

    public function test_non_tv_product_remains_outside_via_product_routing(): void
    {
        $result = app(ProductRequestResolver::class)->resolve(
            'Criar fluxo de compras e licitações para fornecedores.'
        );

        $this->assertSame('gov360', $result['resolved_product']);
        $this->assertNull($result['via_product']);
        $this->assertNull($result['via_task']);
        $this->assertFalse($result['via_ready']);
    }
}
