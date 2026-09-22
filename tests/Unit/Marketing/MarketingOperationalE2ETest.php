<?php

namespace Tests\Unit\Marketing;

use App\Filament\Pages\MarketingDashboard;
use App\Marketing\Application\MarketingAgentExecutor;
use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketingOperationalE2ETest extends TestCase
{
    public function test_vitrine_social_midia_operational_e2e_sequence_is_complete_and_safe(): void
    {
        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            app(SimulatedMarketingAgentExecutor::class),
            app(SchemaContractValidator::class),
        );

        $this->assertSame([
            ['product_market_strategist'],
            ['campaign_planner'],
            ['copy_content'],
            ['creative_director', 'video_producer'],
            ['social_distribution'],
            ['qa_brand_guardian'],
        ], $result['execution_batches']);

        $this->assertSame('completed', $result['status']);
        $this->assertSame('approved', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertCount(7, $result['artifacts']);

        $expectedAgents = [
            'product_market_strategist',
            'campaign_planner',
            'copy_content',
            'creative_director',
            'video_producer',
            'social_distribution',
            'qa_brand_guardian',
        ];

        $this->assertSame($expectedAgents, array_keys($result['state']['tasks']));

        foreach ($result['state']['tasks'] as $task) {
            $this->assertSame('completed', $task['status']);
            $this->assertNotNull($task['output_ref']);
        }
    }

    public function test_qa_block_blocks_campaign_and_still_never_publishes_or_spends(): void
    {
        $executor = new class(app(SimulatedMarketingAgentExecutor::class)) implements MarketingAgentExecutor {
            public function __construct(private SimulatedMarketingAgentExecutor $delegate)
            {
            }

            public function execute(string $agentId, array $campaign, array $inputs): array
            {
                $output = $this->delegate->execute($agentId, $campaign, $inputs);

                if ($agentId === 'qa_brand_guardian') {
                    $output['result'] = 'blocked';
                    $output['summary']['blocking_issues'] = 1;
                    $output['issues'] = [['type' => 'brand', 'severity' => 'blocking']];
                    $output['approved_item_ids'] = [];
                    $output['revision_item_ids'] = array_keys($inputs);
                }

                return $output;
            }

            public function metadataFor(string $agentId): array
            {
                return ['provider' => 'simulated', 'fallback' => false];
            }
        };

        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            $executor,
            app(SchemaContractValidator::class),
        );

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('blocked', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertSame('blocked', $result['state']['tasks']['qa_brand_guardian']['status']);
        $this->assertSame('QA did not approve the campaign.', $result['state']['blocked_reason']);
    }

    public function test_marketing_dashboard_recognizes_revision_language_and_targets_existing_piece(): void
    {
        $page = app(MarketingDashboard::class);
        $page->flowJobs = [
            [
                'id' => 'MKT-AUTO-REV-001',
                'title' => 'Card Lista VIP',
                'status' => 'EM_QA',
                'type' => 'image',
                'format' => 'ad_1_1',
            ],
        ];

        $revisionDetector = new \ReflectionMethod($page, 'isRevisionRequest');
        $revisionDetector->setAccessible(true);
        $this->assertTrue($revisionDetector->invoke($page, 'Corrija o Card Lista VIP e aumente o destaque do CTA.'));
        $this->assertFalse($revisionDetector->invoke($page, 'Crie uma nova campanha para amanhã.'));

        $targetResolver = new \ReflectionMethod($page, 'resolveRevisionTargetIndex');
        $targetResolver->setAccessible(true);
        $this->assertSame(0, $targetResolver->invoke($page, 'Ajustar o Card Lista VIP.'));
    }

    public function test_marketing_dashboard_routes_structured_campaign_plan_through_centro_ia_first(): void
    {
        config()->set('marketing_agents.hub.url', 'https://centro-ia.test/execute');
        config()->set('marketing_agents.hub.token', 'test-token');
        config()->set('marketing_agents.hub.project_id', 'marketing-test');
        config()->set('marketing_agents.hub.capability', 'marketing_generation');

        Http::fake([
            'https://centro-ia.test/execute' => Http::response([
                'ok' => true,
                'output_text' => '{"campaign":{"name":"Hub"},"jobs":[{"type":"image","format":"ad_1_1"}]}',
                'model' => 'hub-test',
            ], 200),
        ]);

        $page = app(MarketingDashboard::class);
        $method = new \ReflectionMethod($page, 'generateDirectorCampaignPlan');
        $method->setAccessible(true);
        $raw = $method->invoke($page, 'system', 'user prompt');

        $this->assertStringContainsString('"name":"Hub"', $raw);
        Http::assertSentCount(1);
    }

    public function test_marketing_dashboard_accepts_director_json_wrapped_in_markdown(): void
    {
        $page = app(MarketingDashboard::class);
        $decoder = new \ReflectionMethod($page, 'decodeDirectorPlan');
        $decoder->setAccessible(true);

        $plan = $decoder->invoke($page, "Resposta do Diretor:\n\x60\x60\x60json\n{\"campaign\":{\"name\":\"Teste\"},\"jobs\":[{\"type\":\"image\",\"format\":\"ad_1_1\"}]}\n\x60\x60\x60");

        $this->assertSame('Teste', $plan['campaign']['name']);
        $this->assertCount(1, $plan['jobs']);
    }


    public function test_revision_never_guesses_between_two_pieces(): void
    {
        $page = app(MarketingDashboard::class);
        $page->flowJobs = [
            ['id' => 'piece-a', 'title' => 'Card principal', 'status' => 'EM_QA'],
            ['id' => 'piece-b', 'title' => 'Card principal', 'status' => 'EM_QA'],
        ];
        $resolve = new \ReflectionMethod($page, 'resolveRevisionTargetIndex');
        $this->assertNull($resolve->invoke($page, 'Corrija a imagem.'));
        $this->assertNull($resolve->invoke($page, 'Corrija o Card principal.'));
        $this->assertSame(1, $resolve->invoke($page, 'Corrija piece-b.'));
        $this->assertNull($resolve->invoke($page, 'Corrija piece-a e piece-b.'));
    }

    public function test_piece_approval_survives_session_and_is_isolated_by_owner_and_context(): void
    {
        [$page, $job] = $this->pieceFixture();
        $page->approveProductionJob($job['id'], $page->getPieceVersion($job));
        $this->assertSame('APROVADO', $page->flowJobs[0]['status']);
        $this->assertSame('EM_QA', $page->flowJobs[1]['status']);
        session()->forget('marketing_workstation.production');
        $newPage = app(MarketingDashboard::class);
        $this->assertCount(1, $newPage->getGalleryJobs());
        $newPage->marketingContextKey = 'other_context';
        $this->assertSame([], $newPage->getGalleryJobs());
        auth()->setUser((new \App\Models\User)->forceFill(['id' => 902, 'company_id' => 10]));
        $this->assertSame([], $page->getGalleryJobs());
    }

    public function test_stale_or_incomplete_piece_cannot_be_approved(): void
    {
        [$page, $job] = $this->pieceFixture();
        $page->approveProductionJob($job['id'], str_repeat('0', 64));
        $this->assertNotNull($page->pieceError);
        $this->assertSame([], $page->getGalleryJobs());
        $job['status'] = 'EM_GERACAO';
        session(['marketing_workstation.production.'.$page->marketingContextKey => ['jobs' => [$job]]]);
        $page->approveProductionJob($job['id'], $page->getPieceVersion($job));
        $this->assertSame([], $page->getGalleryJobs());
    }

    public function test_editorial_date_uses_brasilia_and_never_claims_external_publication(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        [$page, $job] = $this->pieceFixture();
        $version = $page->getPieceVersion($job);
        $page->approveProductionJob($job['id'], $version);
        $page->pieceScheduleInputs[$job['id']] = 'not-a-date';
        $page->planPiecePublication($job['id'], $version);
        $this->assertNotNull($page->pieceError);
        $this->assertSame('APROVADO', $page->getGalleryJobs()[0]['status']);
        $page->pieceScheduleInputs[$job['id']] = now('America/Sao_Paulo')->addDays(2)->format('Y-m-d').'T15:30';
        $page->planPiecePublication($job['id'], $version);
        $saved = $page->getGalleryJobs()[0];
        $this->assertSame('PLANEJADO_EDITORIAL', $saved['status']);
        $this->assertSame('18:30', \Carbon\CarbonImmutable::parse($saved['scheduled_at'])->utc()->format('H:i'));
        $page->publishPieceNow($job['id'], $version);
        $this->assertStringContainsString('nada foi publicado', $page->pieceError);
        $this->assertSame('PLANEJADO_EDITORIAL', $page->getGalleryJobs()[0]['status']);
        $page->keepPieceInGallery($job['id'], $version);
        $this->assertNull($page->getGalleryJobs()[0]['scheduled_at']);
        $page->flowJobId = $job['id'];
        $page->setFlowJobStatus('PUBLICADO');
        $this->assertSame('APROVADO', $page->getGalleryJobs()[0]['status']);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_failed_revision_invalidates_only_selected_approval_and_preserves_previous_version(): void
    {
        [$page, $job] = $this->pieceFixture();
        $page->approveProductionJob($job['id'], $page->getPieceVersion($job));
        // Fail before contacting a provider; no database or paid generation is involved.
        app()->bind(\App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer::class,
            static fn () => throw new \RuntimeException('Controlled generation failure'));
        $page->pieceRevisionInputs[$job['id']] = 'Trocar somente o enquadramento.';
        $page->requestPieceRevision($job['id'], $page->getPieceVersion($job));
        $this->assertSame([], $page->getGalleryJobs());
        $this->assertSame('ERRO', $page->flowJobs[0]['status']);
        $this->assertNull($page->flowJobs[0]['approved_version']);
        $this->assertSame('EM_QA', $page->flowJobs[1]['status']);
        $this->assertSame($job['asset_url'], $page->flowJobs[0]['revision_history'][0]['asset_url']);
    }

    public function test_dashboard_view_compiles_after_piece_controls_are_added(): void
    {
        $source = file_get_contents(resource_path('views/filament/pages/marketing-dashboard-exact.blade.php'));
        // This isolated runner has no icon registry; validate all Blade directives and PHP expressions.
        $compiled = app('blade.compiler')->compileString(preg_replace('/<\/?x-[^>]+>/', '', $source));
        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }


    public function test_revised_video_returns_to_review_and_requires_new_version_approval(): void
    {
        [$page, $job] = $this->pieceFixture();
        $oldVersion = $page->getPieceVersion($job);
        $page->approveProductionJob($job['id'], $oldVersion);
        app()->instance(\App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer::class, new class {
            public function dispatch(...$arguments): array
            {
                return ['status' => 'processing', 'job_ref' => 'test-video-revision'];
            }
            public function refresh(string $jobRef): array
            {
                return ['status' => 'completed', 'render_ref' => 'https://media.test/revised.mp4'];
            }
        });
        $page->pieceRevisionInputs[$job['id']] = 'Ajustar somente o enquadramento.';
        $page->requestPieceRevision($job['id'], $oldVersion);
        $this->assertSame('EM_GERACAO', $page->flowJobs[0]['status']);
        $this->assertSame([], $page->getGalleryJobs());
        $page->refreshProductionBoard();
        $this->assertSame('GERADO', $page->flowJobs[0]['status']);
        $page->approveProductionJob($job['id'], $oldVersion);
        $this->assertNotNull($page->pieceError);
        $page->approveProductionJob($job['id'], $page->getPieceVersion($page->flowJobs[0]));
        $this->assertCount(1, $page->getGalleryJobs());
        $this->assertSame('https://media.test/revised.mp4', $page->getGalleryJobs()[0]['asset_url']);
        session()->forget('marketing_workstation.production');
        $newPage = app(MarketingDashboard::class);
        $newPage->hydrate();
        $this->assertCount(2, $newPage->flowJobs);
    }

    public function test_direct_fallback_uses_openrouter_when_gemini_is_exhausted(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', 'test-gemini');
        config()->set('marketing_video.gemini_veo.base_url', 'https://gemini.test/v1beta');
        config()->set('marketing_agents.native_studio.director_model', 'gemini-test');
        config()->set('marketing_agents.native_studio.openrouter_model', 'openai/gpt-4o-mini');
        putenv('OPENROUTER_API_KEY=test-openrouter');

        Http::fake([
            'https://gemini.test/*' => Http::response([
                'error' => ['status' => 'RESOURCE_EXHAUSTED'],
            ], 402),
            'https://openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'PACOTE_OPENROUTER_OK']]],
            ], 200),
        ]);

        try {
            $page = app(MarketingDashboard::class);
            $method = new \ReflectionMethod($page, 'generateFlowPackageWithGemini');
            $method->setAccessible(true);
            $result = $method->invoke($page, 'system', 'user');

            $this->assertSame('PACOTE_OPENROUTER_OK', $result);
            Http::assertSentCount(3);
        } finally {
            putenv('OPENROUTER_API_KEY');
        }
    }

    private function pieceFixture(): array
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        config(['cache.default' => 'array']);
        auth()->setUser((new \App\Models\User)->forceFill(['id' => 901, 'company_id' => 10]));
        $page = app(MarketingDashboard::class);
        $job = [
            'id' => 'piece-a', 'title' => 'Reel principal', 'status' => 'EM_QA',
            'type' => 'video', 'format' => 'reel_9_16', 'asset_url' => 'https://media.test/piece-a.mp4',
            'director_job' => ['type' => 'video', 'format' => 'reel_9_16', 'idea' => 'Cena original'],
        ];
        session(['marketing_workstation.production.'.$page->marketingContextKey => ['jobs' => [
            $job, array_replace($job, ['id' => 'piece-b', 'title' => 'Outro Reel']),
        ]]]);
        return [$page, $job];
    }

    /** @return array<string, mixed> */
    private function campaign(): array
    {
        return [
            'campaign_id' => 'VSM-E2E-OP-001',
            'tenant_id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'name' => 'Lançamento Vitrine Social Mídia',
            'objective' => 'Gerar demonstrações comerciais qualificadas',
            'automation_mode' => 'assisted',
            'status' => 'ready',
            'known_facts' => [
                'Produto da Vitrine IA Pro',
                'Publicação depende de aprovação humana',
            ],
            'missing_information' => [],
            'restrictions' => [
                'Não publicar',
                'Não contratar mídia',
                'Não inventar preços',
            ],
        ];
    }
}
