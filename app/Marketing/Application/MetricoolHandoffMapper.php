<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use DomainException;

final class MetricoolHandoffMapper
{
    /**
     * Maps an approved SocialDistributionHandoff into a Metricool-safe draft
     * intent. This method never schedules or publishes anything by itself.
     *
     * @param array<string, mixed> $handoff
     * @return array<string, mixed>
     */
    public function map(array $handoff, string $brandId, string $timezone = 'America/Sao_Paulo'): array
    {
        if (($handoff['status'] ?? null) !== 'ready_for_review') {
            throw new DomainException('Distribution handoff must be ready for review.');
        }

        if (($handoff['approval']['result'] ?? null) !== 'approved') {
            throw new DomainException('Approved QA handoff is required for Metricool.');
        }

        if (($handoff['organic']['provider'] ?? null) !== 'metricool') {
            throw new DomainException('Distribution handoff is not configured for Metricool.');
        }

        if (($handoff['organic']['auto_publish'] ?? true) !== false) {
            throw new DomainException('Metricool handoff must keep auto publication disabled.');
        }

        $brandId = trim($brandId);
        if ($brandId === '') {
            throw new DomainException('Metricool brand id is required.');
        }

        $channels = array_values(array_intersect(
            ['facebook', 'instagram'],
            array_map('strtolower', (array) ($handoff['organic']['channels'] ?? [])),
        ));

        if ($channels === []) {
            throw new DomainException('Metricool handoff requires Facebook and/or Instagram.');
        }

        $providers = array_map(
            static fn (string $channel): array => ['network' => $channel],
            $channels,
        );

        return [
            'provider' => 'metricool',
            'brand_id' => $brandId,
            'timezone' => $timezone,
            'handoff_id' => $handoff['handoff_id'] ?? null,
            'idempotency_key' => $handoff['idempotency_key'] ?? null,
            'asset' => $handoff['asset'] ?? [],
            'mode' => 'draft_review_only',
            'publish_action' => 'none',
            'paid_media_action' => 'none',
            'metricool_info' => [
                'autoPublish' => false,
                'draft' => true,
                'providers' => $providers,
                'facebookData' => in_array('facebook', $channels, true)
                    ? ['type' => 'REEL']
                    : null,
                'instagramData' => in_array('instagram', $channels, true)
                    ? [
                        'type' => 'REEL',
                        'showReelOnFeed' => true,
                        'isAiGenerated' => true,
                    ]
                    : null,
                'media' => [],
                'text' => '',
                'firstCommentText' => '',
                'shortener' => false,
                'smartLinkData' => ['ids' => []],
            ],
            'requirements' => [
                'public_media_url_required_before_scheduling' => true,
                'caption_required_before_scheduling' => true,
                'future_publication_date_required_before_scheduling' => true,
                'human_review_required' => true,
            ],
            'status' => 'integration_ready_not_scheduled',
        ];
    }
}
