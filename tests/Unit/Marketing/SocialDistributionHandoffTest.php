<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\SocialDistributionHandoff;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialDistributionHandoffTest extends TestCase
{
    #[Test]
    public function it_prepares_review_only_metricool_handoff_without_publishing_or_spending(): void
    {
        $handoff = app(SocialDistributionHandoff::class)->prepare(
            ['campaign_id' => 'VSM-LAUNCH-001'],
            [
                'distribution_plan_id' => 'DISTRIBUTION-VSM-LAUNCH-001',
                'calendar' => [
                    ['content_id' => 'REEL-01', 'channel' => 'instagram', 'slot' => 'D1-10:00'],
                    ['content_id' => 'REEL-01', 'channel' => 'facebook', 'slot' => 'D1-10:00'],
                ],
                'channel_adaptations' => [],
            ],
            ['qa_report_id' => 'QA-VSM-LAUNCH-001', 'result' => 'approved'],
            [
                'request_id' => 'REEL-01-VITRINE-SOCIAL-MIDIA-20260911',
                'version_id' => 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1',
                'type' => 'video',
            ],
        );

        $this->assertSame('metricool', $handoff['organic']['provider']);
        $this->assertSame(['facebook', 'instagram'], $handoff['organic']['channels']);
        $this->assertSame('review_required', $handoff['organic']['mode']);
        $this->assertFalse($handoff['organic']['auto_publish']);
        $this->assertFalse($handoff['organic']['scheduled']);
        $this->assertFalse($handoff['organic']['published']);
        $this->assertSame('none', $handoff['paid_media']['action']);
        $this->assertFalse($handoff['paid_media']['spent']);
        $this->assertSame('ready_for_review', $handoff['status']);
        $this->assertNotEmpty($handoff['idempotency_key']);
    }

    #[Test]
    public function it_requires_qa_approval(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('QA approval is required before distribution handoff.');

        app(SocialDistributionHandoff::class)->prepare(
            ['campaign_id' => 'VSM-LAUNCH-001'],
            ['calendar' => [['channel' => 'instagram']]],
            ['qa_report_id' => 'QA-VSM-LAUNCH-001', 'result' => 'blocked'],
            ['version_id' => 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1'],
        );
    }

    #[Test]
    public function it_is_deterministic_for_the_same_approved_asset(): void
    {
        $service = app(SocialDistributionHandoff::class);
        $campaign = ['campaign_id' => 'VSM-LAUNCH-001'];
        $distribution = [
            'distribution_plan_id' => 'DISTRIBUTION-VSM-LAUNCH-001',
            'calendar' => [
                ['channel' => 'instagram'],
                ['channel' => 'facebook'],
            ],
        ];
        $qa = ['qa_report_id' => 'QA-VSM-LAUNCH-001', 'result' => 'approved'];
        $asset = ['version_id' => 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1'];

        $first = $service->prepare($campaign, $distribution, $qa, $asset);
        $second = $service->prepare($campaign, $distribution, $qa, $asset);

        $this->assertSame($first['handoff_id'], $second['handoff_id']);
        $this->assertSame($first['idempotency_key'], $second['idempotency_key']);
    }

    #[Test]
    public function direct_meta_publisher_refuses_to_publish_without_connected_account(): void
    {
        config()->set('marketing_agents.publisher.meta', [
            'base_url' => 'https://graph.facebook.com',
            'graph_version' => null,
            'access_token' => null,
            'instagram_user_id' => null,
            'facebook_page_id' => null,
        ]);

        $result = app(SocialDistributionHandoff::class)->publishMetaNow([
            'type' => 'image',
            'asset_url' => 'https://cdn.example.test/creative.jpg',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertSame('PUBLISHER_NOT_CONNECTED', $result['status']);
    }

    #[Test]
    public function direct_meta_publisher_marks_image_published_only_after_provider_id(): void
    {
        config()->set('marketing_agents.publisher.meta', [
            'base_url' => 'https://graph.facebook.com',
            'graph_version' => 'v-test',
            'access_token' => 'test-token',
            'instagram_user_id' => 'ig-test',
            'facebook_page_id' => null,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/v-test/ig-test/media' => \Illuminate\Support\Facades\Http::response(['id' => 'container-1'], 200),
            'https://graph.facebook.com/v-test/ig-test/media_publish' => \Illuminate\Support\Facades\Http::response(['id' => 'media-1'], 200),
        ]);

        $result = app(SocialDistributionHandoff::class)->publishMetaNow([
            'type' => 'image',
            'asset_url' => 'https://cdn.example.test/creative.jpg',
            'caption' => 'Legenda de teste',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('PUBLISHED', $result['status']);
        $this->assertSame('instagram', $result['channel']);
        $this->assertSame('media-1', $result['external_id']);
    }
}
