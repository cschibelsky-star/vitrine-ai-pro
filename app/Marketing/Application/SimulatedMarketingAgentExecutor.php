<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use InvalidArgumentException;

final class SimulatedMarketingAgentExecutor
{
    /**
     * @param array<string, mixed> $campaign
     * @param array<string, array<string, mixed>> $inputs
     * @return array<string, mixed>
     */
    public function execute(string $agentId, array $campaign, array $inputs): array
    {
        $campaignId = (string) $campaign['campaign_id'];

        return match ($agentId) {
            'product_market_strategist' => [
                'strategy_id' => "STRATEGY-{$campaignId}",
                'campaign_id' => $campaignId,
                'product_readiness' => ['ready' => true, 'missing_information' => []],
                'icp' => [['segment' => 'pequenas empresas', 'need' => 'presença digital consistente']],
                'personas' => [['name' => 'Gestor de pequeno negócio', 'role' => 'decisor']],
                'pain_points' => ['falta de tempo', 'produção irregular', 'custo de equipe completa'],
                'desired_outcomes' => ['presença profissional', 'conteúdo recorrente', 'mais oportunidades'],
                'value_proposition' => 'Operação de social media com IA, planejamento e supervisão em um único produto.',
                'positioning' => 'Equipe inteligente de social media para pequenas empresas.',
                'differentiators' => ['produção integrada', 'aprovação assistida', 'histórico mensurável'],
                'objections' => [['objection' => 'conteúdo genérico', 'response' => 'DNA de marca e aprovação humana']],
                'core_message' => 'Sua empresa presente nas redes sociais sem montar uma equipe inteira.',
                'campaign_concept' => 'Sua marca sempre presente',
                'recommended_channels' => ['instagram', 'facebook', 'linkedin'],
                'assumptions' => [],
                'evidence_refs' => ['campaign:known_facts'],
                'open_questions' => [],
                'status' => 'completed',
            ],
            'campaign_planner' => [
                'plan_id' => "PLAN-{$campaignId}",
                'campaign_id' => $campaignId,
                'objective' => (string) $campaign['objective'],
                'duration_days' => 21,
                'phases' => ['awareness', 'consideration', 'conversion'],
                'channels' => $inputs['product_market_strategist']['recommended_channels'],
                'funnel' => ['discover', 'understand', 'request_demo'],
                'deliverables' => ['posts' => 12, 'carousels' => 4, 'reels' => 6, 'stories' => 15],
                'content_matrix' => [['pillar' => 'produtividade', 'format' => 'carousel']],
                'cta_strategy' => ['Solicitar demonstração'],
                'kpis' => ['qualified_leads', 'demo_requests', 'conversion_rate'],
                'dependencies' => ['approved_product_dna'],
                'risks' => [],
                'status' => 'completed',
            ],
            'copy_content' => [
                'content_package_id' => "CONTENT-{$campaignId}",
                'campaign_id' => $campaignId,
                'message_hierarchy' => [
                    'headline' => 'Sua marca sempre presente.',
                    'support' => 'Planejamento e conteúdo com IA, sob seu controle.',
                    'cta' => 'Solicitar demonstração',
                ],
                'content_items' => [['id' => 'POST-001', 'channel' => 'instagram', 'copy' => 'Transforme sua presença digital.']],
                'carousel_scripts' => [['id' => 'CAROUSEL-001', 'slides' => 5]],
                'story_sequences' => [['id' => 'STORY-001', 'frames' => 3]],
                'video_scripts' => [['id' => 'VIDEO-001', 'duration_seconds' => 30]],
                'email_sequences' => [],
                'whatsapp_sequences' => [],
                'landing_page_copy' => ['headline' => 'Social media com inteligência e consistência.'],
                'ad_variations' => [],
                'assumptions' => [],
                'status' => 'completed',
            ],
            'creative_director' => [
                'creative_package_id' => "CREATIVE-{$campaignId}",
                'visual_direction' => ['style' => 'tecnologia acessível', 'contrast' => 'WCAG AA'],
                'assets' => [['id' => 'ART-POST-001', 'source_content_id' => 'POST-001', 'status' => 'brief_ready']],
                'template_definitions' => [['format' => '1080x1350', 'channel' => 'instagram']],
                'missing_assets' => [],
                'status' => 'completed',
            ],
            'video_producer' => [
                'video_package_id' => "VIDEO-{$campaignId}",
                'videos' => [['id' => 'VIDEO-001', 'duration_seconds' => 30, 'status' => 'storyboard_ready']],
                'voice_requirements' => ['language' => 'pt-BR', 'tone' => 'natural'],
                'render_requirements' => ['formats' => ['9:16', '1:1']],
                'missing_assets' => [],
                'status' => 'completed',
            ],
            'social_distribution' => [
                'distribution_plan_id' => "DISTRIBUTION-{$campaignId}",
                'calendar' => [['content_id' => 'POST-001', 'channel' => 'instagram', 'slot' => 'D1-10:00']],
                'channel_adaptations' => [['source_id' => 'POST-001', 'channel' => 'linkedin']],
                'sequence_logic' => ['educate', 'demonstrate', 'convert'],
                'conflicts' => [],
                'status' => 'completed',
            ],
            'qa_brand_guardian' => [
                'qa_report_id' => "QA-{$campaignId}",
                'campaign_id' => $campaignId,
                'result' => 'approved',
                'summary' => ['checked_artifacts' => count($inputs), 'blocking_issues' => 0],
                'checks' => ['brand' => true, 'facts' => true, 'cta' => true, 'publication_disabled' => true],
                'issues' => [],
                'approved_item_ids' => array_keys($inputs),
                'revision_item_ids' => [],
                'status' => 'completed',
            ],
            default => throw new InvalidArgumentException("No simulated executor for [{$agentId}]."),
        };
    }
}
