<?php

namespace Tests\Unit\Factory\AI\Via;

use App\Factory\AI\Via\Products\Procurement\ProcurementViaService;
use App\Factory\AI\Via\Products\SocialMedia\SocialMediaViaService;
use App\Factory\AI\Via\Products\TvDigital\TvDigitalViaService;
use App\Factory\AI\Via\Services\ViaOrchestrator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ViaProductAdaptersTest extends TestCase
{
    public function test_social_media_adapter_builds_standardized_payload(): void
    {
        $via = $this->createMock(ViaOrchestrator::class);

        $via->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(fn (array $request): bool =>
                    $request['task'] === 'social_media_content_generation'
                    && $request['briefing']['channel'] === 'instagram'
                    && $request['briefing']['topic'] === 'Campanha institucional'
                    && $request['output']['include_hashtags'] === true
                ),
                $this->callback(fn (array $context): bool =>
                    $context['product'] === 'social-midia-ia'
                    && $context['company_id'] === 10
                    && $context['metadata']['module'] === 'social-media'
                ),
            )
            ->willReturn(['ok' => true]);

        $service = new SocialMediaViaService($via);

        $result = $service->generate([
            'topic' => 'Campanha institucional',
            'channel' => 'instagram',
        ], [
            'company_id' => 10,
        ]);

        $this->assertTrue($result['ok']);
    }

    public function test_procurement_adapter_builds_pending_issue_payload(): void
    {
        $via = $this->createMock(ViaOrchestrator::class);

        $via->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(fn (array $request): bool =>
                    $request['task'] === 'pending_issue_analysis'
                    && $request['domain'] === 'public_procurement'
                    && $request['input']['pending_issue'] === 'Ausência de reserva orçamentária'
                    && $request['output']['plain_language_explanation'] === true
                ),
                $this->callback(fn (array $context): bool =>
                    $context['product'] === 'agente-compras-ia'
                    && $context['metadata']['module'] === 'procurement'
                ),
            )
            ->willReturn(['ok' => true]);

        $service = new ProcurementViaService($via);

        $result = $service->analyzePendingIssue([
            'pending_issue' => 'Ausência de reserva orçamentária',
        ]);

        $this->assertTrue($result['ok']);
    }

    public function test_tv_digital_adapter_applies_editorial_rules(): void
    {
        $via = $this->createMock(ViaOrchestrator::class);

        $via->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(fn (array $request): bool =>
                    $request['task'] === 'article_expansion'
                    && $request['domain'] === 'digital_newsroom'
                    && $request['input']['city'] === 'Sumaré'
                    && $request['editorial_rules']['preserve_facts'] === true
                    && $request['editorial_rules']['do_not_invent_sources'] === true
                ),
                $this->callback(fn (array $context): bool =>
                    $context['product'] === 'tv-digital-enterprise'
                    && $context['metadata']['module'] === 'tv-digital'
                    && $context['metadata']['city'] === 'Sumaré'
                ),
            )
            ->willReturn(['ok' => true]);

        $service = new TvDigitalViaService($via);

        $result = $service->expandArticle([
            'title' => 'Nova unidade pública é inaugurada',
            'source_text' => 'Texto original da notícia.',
            'city' => 'Sumaré',
        ]);

        $this->assertTrue($result['ok']);
    }

    public function test_procurement_adapter_rejects_unsupported_task(): void
    {
        $service = new ProcurementViaService($this->createMock(ViaOrchestrator::class));

        $this->expectException(InvalidArgumentException::class);

        $service->execute('unsupported_task', []);
    }

    public function test_tv_digital_adapter_rejects_unsupported_task(): void
    {
        $service = new TvDigitalViaService($this->createMock(ViaOrchestrator::class));

        $this->expectException(InvalidArgumentException::class);

        $service->execute('unsupported_task', []);
    }
}
